<?php

namespace common\modules\recruitment\controllers;

use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use common\modules\recruitment\models\Applicant;
use common\modules\recruitment\models\Application as JobApplication;
use common\modules\recruitment\models\Job;
use common\modules\recruitment\services\ApplicantConversionService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class RecruiterController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'convert-applicant' => ['post'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->module->ensureTables();
        return parent::beforeAction($action);
    }

    public function actionLogin()
    {
        if ($this->isRecruiter()) {
            return $this->redirect(['dashboard']);
        }

        $model = new Applicant();
        $model->scenario = 'login';

        if ($model->load(Yii::$app->request->post())) {
            $recruiter = Applicant::find()
                ->where(['role_id' => Applicant::ROLE_RECRUITER])
                ->andWhere([
                    'or',
                    ['email' => $model->email],
                    ['username' => $model->email],
                ])
                ->one();

            if ($recruiter && $recruiter->validatePassword((string)$model->password)) {
                $this->loginRecruiter($recruiter);
                return $this->redirect(['dashboard']);
            }
            $model->addError('password', 'Invalid recruiter credentials.');
        }

        return $this->render('login', ['model' => $model]);
    }

    public function actionDashboard()
    {
        $this->requireRecruiter();

        $recruiterId = $this->currentRecruiterId();
        $jobIds = Job::find()->select('job_id')->where(['recruiter_id' => $recruiterId]);

        return $this->render('dashboard', [
            'openJobs' => (int)Job::find()->where(['recruiter_id' => $recruiterId, 'status' => Job::STATUS_PUBLISHED])->count(),
            'draftJobs' => (int)Job::find()->where(['recruiter_id' => $recruiterId, 'status' => Job::STATUS_DRAFT])->count(),
            'applications' => (int)JobApplication::find()->where(['job_id' => $jobIds])->count(),
            'newApplications' => (int)JobApplication::find()->where(['job_id' => $jobIds, 'application_status' => [JobApplication::STATUS_PENDING, 'New']])->count(),
        ]);
    }

    public function actionApplications()
    {
        $this->requireRecruiter();

        $search = trim((string)Yii::$app->request->get('search', ''));
        $status = (string)Yii::$app->request->get('status', '');
        $query = JobApplication::find()
            ->alias('a')
            ->joinWith(['job j', 'applicant u'])
            ->where(['j.recruiter_id' => $this->currentRecruiterId()])
            ->orderBy(['a.applied_at' => SORT_DESC, 'a.application_id' => SORT_DESC]);

        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'u.full_name', $search],
                ['like', 'u.email', $search],
                ['like', 'j.job_title', $search],
            ]);
        }

        if (array_key_exists($status, JobApplication::statusOptions())) {
            $query->andWhere(['a.application_status' => $status]);
        } else {
            $status = '';
        }

        if (Yii::$app->request->isPost) {
            $application = $this->findOwnedApplication((int)Yii::$app->request->post('application_id'));
            $action = Yii::$app->request->post('action');

            if ($action === 'update_status') {
                $newStatus = (string)Yii::$app->request->post('application_status', JobApplication::STATUS_NEW);
                if (array_key_exists($newStatus, JobApplication::statusOptions())) {
                    $application->application_status = $newStatus;
                    $application->save(false);
                    Yii::$app->session->setFlash('success', 'Application status updated.');
                }
            } elseif ($action === 'schedule_interview') {
                $date = Yii::$app->request->post('interview_date');
                $time = Yii::$app->request->post('interview_time');
                $application->interview_at = $date && $time ? $date . ' ' . $time . ':00' : null;
                $application->interview_type = Yii::$app->request->post('interview_type', 'Google Meet');
                $application->application_status = JobApplication::STATUS_INTERVIEWING;
                if ($application->save()) {
                    Yii::$app->session->setFlash('success', 'Interview scheduled.');
                }
            } elseif ($action === 'save_feedback') {
                $application->feedback_rating = Yii::$app->request->post('feedback_rating') ?: null;
                $application->feedback_notes = trim((string)Yii::$app->request->post('feedback_notes', ''));
                if ($application->save()) {
                    Yii::$app->session->setFlash('success', 'Feedback saved.');
                }
            }

            return $this->redirect(['applications', 'status' => $status, 'search' => $search]);
        }

        return $this->render('applications', [
            'dataProvider' => new ActiveDataProvider([
                'query' => $query,
                'pagination' => ['pageSize' => 20],
            ]),
            'statusCounts' => $this->statusCounts(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function actionConvertApplicant($id)
    {
        $this->requireRecruiter();
        $application = $this->findOwnedApplication((int)$id);
        $applicant = $application->applicant;
        $job = $application->job;

        $department = $this->resolveDepartment($job->department);
        if ($department === null) {
            Yii::$app->session->setFlash('error', 'Create at least one HR department before converting applicants.');
            return $this->redirect(['applications']);
        }

        try {
            $result = (new ApplicantConversionService())->convert($applicant, $job, $department);
            $application->application_status = JobApplication::STATUS_HIRED;
            $application->save(false);
            $message = 'Applicant converted to employee.';
            if ($result['tempPassword'] !== null) {
                $message .= ' Employee portal password: ' . $result['tempPassword'];
            }
            Yii::$app->session->setFlash('success', $message);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'already exists')) {
                Yii::$app->session->setFlash('info', $msg);
            } else {
                Yii::$app->session->setFlash('error', 'Could not convert applicant: ' . $msg);
            }
        }

        return $this->redirect(['applications']);
    }

    public function actionLogout()
    {
        Yii::$app->session->remove('rms_recruiter_id');
        Yii::$app->session->remove('rms_recruiter_name');
        return $this->redirect(['login']);
    }

    protected function loginRecruiter(Applicant $recruiter)
    {
        Yii::$app->session->set('rms_recruiter_id', (int)$recruiter->user_id);
        Yii::$app->session->set('rms_recruiter_name', $recruiter->full_name);
    }

    protected function isRecruiter()
    {
        return (bool)Yii::$app->session->get('rms_recruiter_id');
    }

    protected function requireRecruiter()
    {
        if (!$this->isRecruiter()) {
            throw new ForbiddenHttpException('Recruiter login is required.');
        }
    }

    protected function currentRecruiterId()
    {
        return (int)Yii::$app->session->get('rms_recruiter_id');
    }

    protected function findOwnedApplication($id)
    {
        $application = JobApplication::find()
            ->alias('a')
            ->joinWith(['job j', 'applicant'])
            ->where(['a.application_id' => $id, 'j.recruiter_id' => $this->currentRecruiterId()])
            ->one();

        if ($application === null) {
            throw new NotFoundHttpException('Application not found.');
        }

        return $application;
    }

    protected function statusCounts()
    {
        $counts = array_fill_keys(array_keys(JobApplication::statusOptions()), 0);
        $rows = (new Query())
            ->select(['a.application_status', 'count' => 'COUNT(*)'])
            ->from(['a' => JobApplication::tableName()])
            ->innerJoin(['j' => Job::tableName()], 'j.job_id = a.job_id')
            ->where(['j.recruiter_id' => $this->currentRecruiterId()])
            ->groupBy('a.application_status')
            ->all();

        foreach ($rows as $row) {
            $counts[$row['application_status']] = (int)$row['count'];
        }

        return $counts;
    }

    protected function resolveDepartment($name)
    {
        if ($name) {
            $department = Department::find()->where(['like', 'name', $name])->one();
            if ($department !== null) {
                return $department;
            }
        }

        return Department::find()->orderBy(['id' => SORT_ASC])->one();
    }
}
