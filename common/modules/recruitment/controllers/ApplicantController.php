<?php

namespace common\modules\recruitment\controllers;

use common\modules\employee\models\Department;
use common\modules\recruitment\models\Applicant;
use common\modules\recruitment\models\Application as JobApplication;
use common\modules\recruitment\models\Job;
use Yii;
use yii\db\IntegrityException;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class ApplicantController extends Controller
{
    /** @var string */
    public $layout = '@common/modules/recruitment/views/layouts/applicant-auth';

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'apply' => ['post'],
                    'withdraw-application' => ['post'],
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->module->ensureTables();

        if (in_array($action->id, ['dashboard', 'profile', 'status', 'apply', 'withdraw-application', 'download-resume'], true)) {
            $this->layout = '@common/modules/recruitment/views/layouts/applicant-main';
            if (!$this->isApplicant() && $action->id !== 'apply') {
                Yii::$app->session->setFlash('error', 'Please sign in to continue.');
                $this->redirect(['login'])->send();
                return false;
            }
        } else {
            $this->layout = '@common/modules/recruitment/views/layouts/applicant-auth';
        }

        return parent::beforeAction($action);
    }

    public function actionLogin()
    {
        if ($this->isApplicant()) {
            return $this->redirect(['dashboard']);
        }

        $model = new Applicant(['scenario' => 'login']);

        if ($model->load(Yii::$app->request->post())) {
            $applicant = Applicant::findOne(['email' => $model->email]);

            if ($applicant === null) {
                $model->addError('password', 'Invalid email or password.');
            } elseif ((int)$applicant->role_id !== Applicant::ROLE_APPLICANT) {
                $model->addError('password', 'This login is for applicants only.');
            } elseif (!$applicant->validatePassword((string)$model->password)) {
                $model->addError('password', 'Invalid email or password.');
            } else {
                $this->loginApplicant($applicant);

                return $this->redirect(['dashboard']);
            }
        }

        return $this->render('login', ['model' => $model]);
    }

    public function actionRegister()
    {
        if ($this->isApplicant()) {
            return $this->redirect(['dashboard']);
        }

        $this->view->params['applicantAuthHeader'] = true;

        $model = new Applicant(['scenario' => 'register']);
        $model->role_id = Applicant::ROLE_APPLICANT;

        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Account created successfully. You can now log in.');

                return $this->redirect(['login']);
            }

            if ($model->hasErrors('email')) {
                $model->addError('email', 'Email already registered.');
            }
            if ($model->hasErrors('phone')) {
                $model->addError('phone', 'This phone number is already registered.');
            }
        }

        return $this->render('register', ['model' => $model]);
    }

    public function actionDashboard()
    {
        $this->view->params['applicantNav'] = 'dashboard';

        $search = trim((string)Yii::$app->request->get('search', ''));
        $department = trim((string)Yii::$app->request->get('department', ''));

        $query = Job::find()
            ->where(['status' => Job::STATUS_PUBLISHED])
            ->orderBy(['created_at' => SORT_DESC, 'job_id' => SORT_DESC]);

        if ($search !== '') {
            $query->andWhere(['like', 'job_title', $search]);
        }

        if ($department !== '') {
            $query->andWhere(['department' => $department]);
        }

        $jobs = $query->all();

        $appliedJobIds = JobApplication::find()
            ->select('job_id')
            ->where(['applicant_id' => $this->currentApplicantId()])
            ->column();

        $departments = Department::find()
            ->select('name')
            ->orderBy(['name' => SORT_ASC])
            ->column();

        // Include job department labels not yet in HR directory (legacy job text values).
        $fromJobs = Job::find()
            ->select('department')
            ->distinct()
            ->where(['status' => Job::STATUS_PUBLISHED])
            ->andWhere(['not', ['department' => null]])
            ->andWhere(['<>', 'department', ''])
            ->column();
        $departments = array_values(array_unique(array_merge($departments, $fromJobs)));
        sort($departments, SORT_NATURAL | SORT_FLAG_CASE);

        return $this->render('dashboard', [
            'jobs' => $jobs,
            'search' => $search,
            'department' => $department,
            'departments' => $departments,
            'appliedJobIds' => array_map('intval', $appliedJobIds),
        ]);
    }

    public function actionApply($id)
    {
        $job = $this->findPublishedJob((int)$id);

        $application = new JobApplication([
            'job_id' => $job->job_id,
            'applicant_id' => $this->currentApplicantId(),
            'application_status' => JobApplication::STATUS_PENDING,
        ]);

        try {
            if ($application->save()) {
                Yii::$app->session->setFlash('success', 'Application submitted successfully.');
            } else {
                Yii::$app->session->setFlash('error', 'Could not submit application.');
            }
        } catch (IntegrityException $exception) {
            Yii::$app->session->setFlash('info', 'You have already applied for this job.');
        }

        return $this->redirect(['dashboard']);
    }

    public function actionProfile()
    {
        $this->view->params['applicantNav'] = 'profile';

        $model = Applicant::findOne($this->currentApplicantId());
        if ($model === null) {
            return $this->redirect(['login']);
        }

        $model->scenario = 'profile';

        if ($model->load(Yii::$app->request->post())) {
            $model->resumeFile = UploadedFile::getInstance($model, 'resumeFile');
            if ($model->validate() && $model->uploadResume(Yii::getAlias('@frontend/web')) && $model->save(false)) {
                Yii::$app->session->setFlash('success', 'Profile updated.');
                Yii::$app->session->set('rms_applicant_name', $model->full_name);

                return $this->redirect(['profile']);
            }
        }

        return $this->render('profile', ['model' => $model]);
    }

    public function actionStatus()
    {
        $this->view->params['applicantNav'] = 'status';

        $applications = JobApplication::find()
            ->where(['applicant_id' => $this->currentApplicantId()])
            ->with('job')
            ->orderBy(['applied_at' => SORT_DESC, 'application_id' => SORT_DESC])
            ->all();

        return $this->render('status', ['applications' => $applications]);
    }

    public function actionWithdrawApplication($id)
    {
        $application = $this->findOwnedApplication((int)$id);

        if (!$application->canApplicantWithdraw()) {
            Yii::$app->session->setFlash('error', 'This application can no longer be withdrawn.');
            return $this->redirect(['status']);
        }

        $jobTitle = trim((string)($application->job->job_title ?? ''));
        $application->delete();
        Yii::$app->session->setFlash(
            'success',
            $jobTitle !== ''
                ? 'Your application for "' . $jobTitle . '" has been withdrawn.'
                : 'Your application has been withdrawn.'
        );

        return $this->redirect(['status']);
    }

    public function actionDownloadResume()
    {
        $model = Applicant::findOne($this->currentApplicantId());
        if ($model === null || !$model->resume_path) {
            throw new NotFoundHttpException('Resume not found.');
        }

        $path = Yii::getAlias('@frontend/web/' . $model->resume_path);
        if (!is_file($path)) {
            throw new NotFoundHttpException('Resume file not found.');
        }

        return Yii::$app->response->sendFile($path, $model->resume_original_name ?: basename($path));
    }

    public function actionLogout()
    {
        Yii::$app->session->remove('rms_applicant_id');
        Yii::$app->session->remove('rms_applicant_name');

        return $this->redirect(['login']);
    }

    protected function loginApplicant(Applicant $applicant): void
    {
        Yii::$app->session->set('rms_applicant_id', (int)$applicant->user_id);
        Yii::$app->session->set('rms_applicant_name', $applicant->full_name);
    }

    protected function isApplicant(): bool
    {
        return (bool)Yii::$app->session->get('rms_applicant_id');
    }

    protected function currentApplicantId(): int
    {
        return (int)Yii::$app->session->get('rms_applicant_id');
    }

    protected function findOwnedApplication(int $applicationId): JobApplication
    {
        $application = JobApplication::find()
            ->with('job')
            ->where([
                'application_id' => $applicationId,
                'applicant_id' => $this->currentApplicantId(),
            ])
            ->one();

        if ($application === null) {
            throw new NotFoundHttpException('Application not found.');
        }

        return $application;
    }

    protected function findPublishedJob(int $id): Job
    {
        $job = Job::findOne(['job_id' => $id, 'status' => Job::STATUS_PUBLISHED]);
        if ($job === null) {
            throw new NotFoundHttpException('Job not found.');
        }

        return $job;
    }
}
