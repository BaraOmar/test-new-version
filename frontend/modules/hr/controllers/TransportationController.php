<?php

namespace frontend\modules\hr\controllers;

use common\modules\transport\services\TransportationService;
use common\traits\HrAccessTrait;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class TransportationController extends Controller
{
    use HrAccessTrait;

    private ?TransportationService $transportationService = null;

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
                    'delete-area' => ['POST'],
                    'delete-bus' => ['POST'],
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
        $this->requireTransportationEnabled();

        $permissionMap = [
            'hub' => ['transportation', 'transportation_areas', 'transportation_buses', 'transportation_assign_buses'],
            'areas' => ['transportation', 'transportation_areas'],
            'create-area' => ['transportation', 'transportation_areas'],
            'update-area' => ['transportation', 'transportation_areas'],
            'delete-area' => ['transportation', 'transportation_areas'],
            'buses' => ['transportation', 'transportation_buses'],
            'create-bus' => ['transportation', 'transportation_buses'],
            'update-bus' => ['transportation', 'transportation_buses'],
            'delete-bus' => ['transportation', 'transportation_buses'],
            'assign-buses' => ['transportation', 'transportation_assign_buses'],
            'assign-bus' => ['transportation', 'transportation_assign_buses'],
        ];

        $actionId = $action->id;
        if (isset($permissionMap[$actionId])) {
            $this->requireAnyPermission($permissionMap[$actionId]);
        } else {
            $this->requirePermission('transportation');
        }

        $this->view->params['bodyClass'] = 'bg-gray-50 text-slate-900 h-screen overflow-hidden';

        return true;
    }

    public function actionHub()
    {
        return $this->render('hub', [
            'userName' => $this->userDisplayName(),
            'currentPage' => 'transportation',
        ]);
    }

    public function actionAreas()
    {
        $search = trim((string)Yii::$app->request->get('search', ''));
        $dataProvider = $this->transportationService()->buildAreasDataProvider($search);
        $dataProvider->pagination->params = array_merge(
            Yii::$app->request->getQueryParams(),
            array_filter(['search' => $search], static fn(string $value): bool => $value !== '')
        );

        return $this->render('areas/index', [
            'dataProvider' => $dataProvider,
            'search' => $search,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'transportation_areas',
        ]);
    }

    public function actionCreateArea()
    {
        if (Yii::$app->request->isPost) {
            $result = $this->transportationService()->saveArea();
            if ($result['success']) {
                Yii::$app->session->setFlash('success', (string)$result['message']);

                return $this->redirect(['areas']);
            }

            Yii::$app->session->setFlash('error', (string)$result['message']);
        }

        return $this->render('areas/form', [
            'area' => null,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'transportation_areas',
        ]);
    }

    public function actionUpdateArea(int $id)
    {
        $area = $this->transportationService()->findArea($id);

        if (Yii::$app->request->isPost) {
            $result = $this->transportationService()->saveArea($id);
            if ($result['success']) {
                Yii::$app->session->setFlash('success', (string)$result['message']);

                return $this->redirect(['areas']);
            }

            Yii::$app->session->setFlash('error', (string)$result['message']);
            $area = $this->transportationService()->findArea($id);
        }

        return $this->render('areas/form', [
            'area' => $area,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'transportation_areas',
        ]);
    }

    public function actionDeleteArea(int $id)
    {
        $result = $this->transportationService()->deleteArea($id);
        Yii::$app->session->setFlash($result['success'] ? 'success' : 'error', (string)$result['message']);

        return $this->redirect(['areas']);
    }

    public function actionBuses()
    {
        $search = trim((string)Yii::$app->request->get('search', ''));
        $dataProvider = $this->transportationService()->buildBusesDataProvider($search);
        $dataProvider->pagination->params = array_merge(
            Yii::$app->request->getQueryParams(),
            array_filter(['search' => $search], static fn(string $value): bool => $value !== '')
        );

        return $this->render('buses/index', [
            'dataProvider' => $dataProvider,
            'search' => $search,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'transportation_buses',
        ]);
    }

    public function actionCreateBus()
    {
        if (Yii::$app->request->isPost) {
            $result = $this->transportationService()->saveBus();
            if ($result['success']) {
                Yii::$app->session->setFlash('success', (string)$result['message']);

                return $this->redirect(['buses']);
            }

            Yii::$app->session->setFlash('error', (string)$result['message']);
        }

        return $this->render('buses/form', [
            'bus' => null,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'transportation_buses',
        ]);
    }

    public function actionUpdateBus(int $id)
    {
        $bus = $this->transportationService()->findBus($id);

        if (Yii::$app->request->isPost) {
            $result = $this->transportationService()->saveBus($id);
            if ($result['success']) {
                Yii::$app->session->setFlash('success', (string)$result['message']);

                return $this->redirect(['buses']);
            }

            Yii::$app->session->setFlash('error', (string)$result['message']);
            $bus = $this->transportationService()->findBus($id);
        }

        return $this->render('buses/form', [
            'bus' => $bus,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'transportation_buses',
        ]);
    }

    public function actionDeleteBus(int $id)
    {
        $result = $this->transportationService()->deleteBus($id);
        Yii::$app->session->setFlash($result['success'] ? 'success' : 'error', (string)$result['message']);

        return $this->redirect(['buses']);
    }

    public function actionAssignBuses()
    {
        $search = trim((string)Yii::$app->request->get('search', ''));
        $dataProvider = $this->transportationService()->buildAssignmentsDataProvider($search);
        $dataProvider->pagination->params = array_merge(
            Yii::$app->request->getQueryParams(),
            array_filter(['search' => $search], static fn(string $value): bool => $value !== '')
        );

        return $this->render('assign-buses/index', [
            'dataProvider' => $dataProvider,
            'search' => $search,
            'userName' => $this->userDisplayName(),
            'currentPage' => 'transportation_assign_buses',
        ]);
    }

    public function actionAssignBus(int $id)
    {
        $bus = $this->transportationService()->findBus($id);
        $service = $this->transportationService();

        if (Yii::$app->request->isPost) {
            $studentIds = array_map('intval', (array)Yii::$app->request->post('student_ids', []));
            $result = $service->saveBusAssignment($id, $studentIds);
            if ($result['success']) {
                Yii::$app->session->setFlash('success', (string)$result['message']);

                return $this->redirect(['assign-buses']);
            }

            Yii::$app->session->setFlash('error', (string)$result['message']);
            $bus = $service->findBus($id);
        }

        return $this->render('assign-buses/form', [
            'bus' => $bus,
            'areaOptions' => $service->areaOptions(true),
            'employeeOptions' => $service->employeeOptions(),
            'studentOptions' => $service->studentOptions(),
            'selectedStudentIds' => $service->assignedStudentIds($id),
            'userName' => $this->userDisplayName(),
            'currentPage' => 'transportation_assign_buses',
        ]);
    }

    private function transportationService(): TransportationService
    {
        if ($this->transportationService === null) {
            $this->transportationService = new TransportationService();
        }

        return $this->transportationService;
    }

    private function requireTransportationEnabled(): void
    {
        if (!TransportationService::isEnabled()) {
            throw new NotFoundHttpException('Transportation module is not available. Run: php yii migrate/up');
        }
    }

    /**
     * @param string[] $permissions
     */
    private function requireAnyPermission(array $permissions): void
    {
        $this->requireHr();
        $user = Yii::$app->user->identity;
        if ($user === null) {
            throw new \yii\web\ForbiddenHttpException('Access denied.');
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return;
            }
        }

        throw new \yii\web\ForbiddenHttpException('Access denied.');
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
