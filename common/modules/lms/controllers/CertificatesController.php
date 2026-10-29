<?php

namespace common\modules\lms\controllers;

use common\components\LmsPortal;
use common\models\User;
use common\modules\employee\services\SchoolStudentCertificateService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CertificatesController extends Controller
{
    public $layout = '@common/modules/lms/views/layouts/portal';

    private ?SchoolStudentCertificateService $certificateService = null;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                    'my' => ['GET'],
                    'roster' => ['GET'],
                    'data' => ['GET'],
                    'print' => ['GET'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $user = Yii::$app->user->identity;
        if (!$user instanceof User || !LmsPortal::canAccessLms($user)) {
            throw new ForbiddenHttpException('LMS access is required.');
        }

        if (!SchoolStudentCertificateService::isEnabled()) {
            throw new NotFoundHttpException('Student certificates are not available. Run migrations first.');
        }

        if (!LmsPortal::canAccessStudentCertificates($user)) {
            throw new ForbiddenHttpException('You do not have access to student certificates.');
        }

        $this->view->params['userName'] = LmsPortal::displayName($user);
        $this->view->params['userRole'] = LmsPortal::roleLabel($user);
        $this->view->params['portalUser'] = $user;

        return true;
    }

    public function actionIndex()
    {
        $user = $this->requireUser();
        if (!LmsPortal::canAccessStudentCertificateManagement($user)) {
            if (LmsPortal::canAccessOwnStudentCertificate($user)) {
                return $this->redirect(['my']);
            }
            throw new ForbiddenHttpException(
                'Only class teachers and department heads can create student certificates.'
            );
        }

        $this->view->params['currentPage'] = 'lms_certificates';
        $options = $this->certificateService()->getStudentCertificateOptions($user);

        return $this->render('index', [
            'studentMode' => false,
            'options' => $options,
            'certificate' => null,
        ]);
    }

    public function actionMy()
    {
        $user = $this->requireUser();
        if (!LmsPortal::canAccessOwnStudentCertificate($user)) {
            if (LmsPortal::canAccessStudentCertificateManagement($user)) {
                return $this->redirect(['index']);
            }
            throw new ForbiddenHttpException('Only students can open their own certificate here.');
        }

        $this->view->params['currentPage'] = 'lms_my_certificate';
        $scope = (string)Yii::$app->request->get('scope', 'all');
        $certificate = $this->certificateService()->getMyStudentCertificatePage($user, $scope);

        return $this->render('index', [
            'studentMode' => true,
            'options' => [
                'ok' => true,
                'can_manage' => false,
                'departments' => [],
                'can_select_department' => false,
                'default_department_id' => false,
                'grades' => [],
                'scopes' => $certificate['scopes'] ?? SchoolStudentCertificateService::scopeRows(),
                'page_title' => $certificate['page_title'] ?? 'My Certificate | LMS',
                'portal_eyebrow' => $certificate['portal_eyebrow'] ?? 'Student Portal',
                'subtitle' => 'Your academic report card for the selected period.',
            ],
            'certificate' => $certificate,
        ]);
    }

    public function actionRoster()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $user = $this->requireUser();
            $sectionId = (int)Yii::$app->request->get('section_id', 0);
            $scope = (string)Yii::$app->request->get('scope', 'all');

            return $this->certificateService()->getSectionCertificateRoster($user, $sectionId, $scope);
        } catch (ForbiddenHttpException|NotFoundHttpException $e) {
            Yii::$app->response->statusCode = $e instanceof NotFoundHttpException ? 404 : 403;

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionData()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $user = $this->requireUser();
            $studentId = (int)Yii::$app->request->get('student_id', 0);
            $scope = (string)Yii::$app->request->get('scope', 'all');
            if (LmsPortal::canAccessOwnStudentCertificate($user) && $studentId <= 0) {
                return $this->certificateService()->getMyStudentCertificatePage($user, $scope);
            }

            return $this->certificateService()->getStudentCertificateData($user, $studentId, $scope);
        } catch (ForbiddenHttpException|NotFoundHttpException $e) {
            Yii::$app->response->statusCode = $e instanceof NotFoundHttpException ? 404 : 403;

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionPrint()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $user = $this->requireUser();
            $studentId = (int)Yii::$app->request->get('student_id', 0);
            $scope = (string)Yii::$app->request->get('scope', 'all');
            if (LmsPortal::canAccessOwnStudentCertificate($user) && $studentId <= 0) {
                $data = $this->certificateService()->getMyStudentCertificatePage($user, $scope);
            } else {
                $data = $this->certificateService()->getStudentCertificateData($user, $studentId, $scope);
            }
            $html = $this->renderPartial('print', ['data' => $data]);

            return [
                'ok' => true,
                'html' => $html,
                'title' => $data['page_title'] ?? ($data['title'] ?? 'Student Certificate'),
            ];
        } catch (ForbiddenHttpException|NotFoundHttpException $e) {
            Yii::$app->response->statusCode = $e instanceof NotFoundHttpException ? 404 : 403;

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function certificateService(): SchoolStudentCertificateService
    {
        if ($this->certificateService === null) {
            $this->certificateService = new SchoolStudentCertificateService();
        }

        return $this->certificateService;
    }

    private function requireUser(): User
    {
        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            throw new ForbiddenHttpException('Authentication is required.');
        }

        return $user;
    }
}
