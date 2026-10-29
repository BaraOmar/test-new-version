<?php

namespace frontend\modules\hr\controllers;

use common\modules\finance\models\FinanceAccountParentProfile;
use common\modules\finance\services\FinanceParentProfileService;
use common\traits\HrAccessTrait;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ParentsController extends Controller
{
    use HrAccessTrait;

    private ?FinanceParentProfileService $parentProfileService = null;

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
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireHr();
        $this->requirePermission('employees');
        $this->requireParentsDirectoryEnabled();
        $this->view->params['bodyClass'] = 'bg-gray-50 text-slate-900 h-screen overflow-hidden';

        return true;
    }

    public function actionIndex()
    {
        $search = trim((string)Yii::$app->request->get('search', ''));
        $dataProvider = $this->parentProfileService()->buildIndexDataProvider($search);
        $dataProvider->pagination->params = array_merge(
            Yii::$app->request->getQueryParams(),
            array_filter([
                'search' => $search,
            ], static fn (string $value): bool => $value !== '')
        );

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'search' => $search,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'parents_directory',
        ]);
    }

    public function actionView(int $id)
    {
        return $this->render('view', [
            'profile' => $this->parentProfileService()->findProfile($id),
            'userName' => $this->userDisplayName(),
            'currentPage' => 'parents_directory',
        ]);
    }

    public function actionCreate()
    {
        if (Yii::$app->request->isPost) {
            $result = $this->parentProfileService()->createProfile();
            if ($result['success'] && $result['profile'] !== null) {
                Yii::$app->session->setFlash('success', (string)$result['message']);
                if (!empty($result['credentials']) && is_array($result['credentials'])) {
                    Yii::$app->session->setFlash('parent_credentials', $result['credentials']);
                }

                return $this->redirect(['index']);
            }

            Yii::$app->session->setFlash('error', (string)$result['message']);
        }

        $posted = Yii::$app->request->isPost ? Yii::$app->request->post() : [];
        $generatedPassword = trim((string)($posted['parent_login_password'] ?? ''));
        if ($generatedPassword === '') {
            $generatedPassword = $this->parentProfileService()->generatePreviewPassword();
        }

        return $this->render('create', [
            'userName' => $this->userDisplayName(),
            'currentPage' => 'parents_directory',
            'posted' => $posted,
            'generatedPassword' => $generatedPassword,
        ]);
    }

    public function actionUpdate(int $id)
    {
        $profile = $this->parentProfileService()->findProfile($id);

        if (Yii::$app->request->isPost) {
            $result = $this->parentProfileService()->updateProfile($id);
            if ($result['success']) {
                Yii::$app->session->setFlash('success', (string)$result['message']);
                if (!empty($result['credentials']) && is_array($result['credentials'])) {
                    Yii::$app->session->setFlash('parent_credentials', $result['credentials']);
                }

                return $this->redirect(['index']);
            }

            Yii::$app->session->setFlash('error', (string)$result['message']);
            $profile = $this->parentProfileService()->findProfile($id);
        }

        return $this->render('update', [
            'profile' => $profile,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'parents_directory',
        ]);
    }

    public function actionRefreshPasswords()
    {
        $result = $this->parentProfileService()->refreshMissingVisiblePasswords();
        if ($result['success']) {
            Yii::$app->session->setFlash('success', (string)$result['message']);
            if ($result['credentials'] !== []) {
                Yii::$app->session->setFlash('parent_credentials_batch', $result['credentials']);
            }
        } else {
            Yii::$app->session->setFlash('error', (string)$result['message']);
        }

        return $this->redirect(['index']);
    }

    public function actionDelete(int $id)
    {
        $confirmation = trim((string)Yii::$app->request->post('confirmation', ''));
        $result = $this->parentProfileService()->deleteProfile($id, $confirmation);
        if ($result['success']) {
            Yii::$app->session->setFlash('success', (string)$result['message']);
        } else {
            Yii::$app->session->setFlash('error', (string)$result['message']);
        }

        return $this->redirect(['index']);
    }

    private function parentProfileService(): FinanceParentProfileService
    {
        if ($this->parentProfileService === null) {
            $this->parentProfileService = new FinanceParentProfileService();
        }

        return $this->parentProfileService;
    }

    private function requireParentsDirectoryEnabled(): void
    {
        if (!FinanceAccountParentProfile::isEnabled()) {
            throw new NotFoundHttpException('Parents directory is not available. Run migrations first.');
        }
    }

    private function userDisplayName(): string
    {
        $user = Yii::$app->user->identity;
        $name = '';
        if ($user !== null && method_exists($user, 'getDisplayName')) {
            $name = trim((string)$user->getDisplayName());
        }

        return $name !== '' ? $name : 'Admin User';
    }
}
