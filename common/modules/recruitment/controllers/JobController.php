<?php

namespace common\modules\recruitment\controllers;

use common\modules\recruitment\models\Applicant;
use common\modules\recruitment\models\Application as JobApplication;
use common\modules\recruitment\models\Job;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class JobController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'close' => ['post'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->module->ensureTables();
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $this->requireRecruiter();

        $search = trim((string)Yii::$app->request->get('search', ''));
        $status = (string)Yii::$app->request->get('status', '');
        $query = Job::find()
            ->where(['recruiter_id' => $this->currentRecruiterId()])
            ->with('applications')
            ->orderBy(['created_at' => SORT_DESC, 'job_id' => SORT_DESC]);

        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'job_title', $search],
                ['like', 'department', $search],
                ['like', 'location', $search],
            ]);
        }

        if (array_key_exists($status, Job::statusOptions())) {
            $query->andWhere(['status' => $status]);
        } else {
            $status = '';
        }

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => $query,
                'pagination' => ['pageSize' => 20],
            ]),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function actionCreate($id = null)
    {
        $this->requireRecruiter();

        $model = $id ? $this->findOwnedJob((int)$id) : new Job([
            'recruiter_id' => $this->currentRecruiterId(),
            'status' => Job::STATUS_DRAFT,
        ]);

        if ($model->load(Yii::$app->request->post())) {
            $model->recruiter_id = $this->currentRecruiterId();
            $submit = Yii::$app->request->post('submit');
            if ($submit === 'publish') {
                $model->status = Job::STATUS_PUBLISHED;
            } elseif ($submit === 'draft') {
                $model->status = Job::STATUS_DRAFT;
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Job saved.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('form', ['model' => $model]);
    }

    public function actionClose($id)
    {
        $this->requireRecruiter();
        $model = $this->findOwnedJob((int)$id);
        $model->status = Job::STATUS_CLOSED;
        $model->save(false);
        JobApplication::closeActiveApplicationsForJob((int)$model->job_id);
        Yii::$app->session->setFlash('success', 'Job closed.');
        return $this->redirect(['index']);
    }

    public function actionDelete($id)
    {
        $this->requireRecruiter();
        $this->findOwnedJob((int)$id)->delete();
        Yii::$app->session->setFlash('success', 'Job deleted.');
        return $this->redirect(['index']);
    }

    protected function requireRecruiter()
    {
        if (!(int)Yii::$app->session->get('rms_recruiter_id')) {
            throw new ForbiddenHttpException('Recruiter login is required.');
        }
    }

    protected function currentRecruiterId()
    {
        return (int)Yii::$app->session->get('rms_recruiter_id');
    }

    protected function findOwnedJob($id)
    {
        $model = Job::findOne(['job_id' => $id, 'recruiter_id' => $this->currentRecruiterId()]);
        if ($model === null) {
            throw new NotFoundHttpException('Job not found.');
        }

        return $model;
    }
}
