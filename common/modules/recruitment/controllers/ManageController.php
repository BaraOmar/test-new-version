<?php

namespace common\modules\recruitment\controllers;

use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use common\modules\recruitment\models\Application as JobApplication;
use common\modules\recruitment\models\Job;
use common\modules\recruitment\services\ApplicantConversionService;
use common\modules\recruitment\services\RecruitmentReportService;
use common\modules\recruitment\traits\RecruiterSessionTrait;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ManageController extends Controller
{
    use RecruiterSessionTrait;

    /** @var string[] */
    protected array $indexRoute = ['index'];

    /** @var string[] */
    protected array $jobsRoute = ['jobs'];

    /** @var string[] */
    protected array $jobFormRoute = ['job-form'];

    /** @var string[] */
    protected array $applicationsRoute = ['applications'];

    /** @var string[] */
    protected array $reportsRoute = ['reports'];

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete-job' => ['post'],
                    'close-job' => ['post'],
                    'open-job' => ['post'],
                    'convert-applicant' => ['post'],
                    'export-report' => ['get'],
                    'download-applicant-resume' => ['get'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $module = $this->module;
        if ($module instanceof \common\modules\recruitment\Module) {
            $module->ensureTables();
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $this->requireRecruiter();

        return $this->render('index', [
            'indexRoute' => $this->indexRoute,
            'jobsRoute' => $this->jobsRoute,
            'jobFormRoute' => $this->jobFormRoute,
            'applicationsRoute' => $this->applicationsRoute,
            'reportsRoute' => $this->reportsRoute,
        ]);
    }

    public function actionJobs()
    {
        $this->requireRecruiter();

        $recruiterId = $this->currentRecruiterId();
        $search = trim((string)Yii::$app->request->get('search', ''));
        $status = (string)Yii::$app->request->get('status', '');
        $department = trim((string)Yii::$app->request->get('department', ''));

        $query = Job::find()
            ->where(['recruiter_id' => $recruiterId])
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

        if ($department !== '') {
            $query->andWhere(['department' => $department]);
        }

        $stats = ['total' => 0, 'published' => 0, 'draft' => 0, 'closed' => 0];
        foreach (Job::find()->where(['recruiter_id' => $recruiterId])->all() as $jobRow) {
            $stats['total']++;
            $key = (string)$jobRow->status;
            if (isset($stats[$key])) {
                $stats[$key]++;
            }
        }

        $departments = Job::find()
            ->select('department')
            ->distinct()
            ->where(['recruiter_id' => $recruiterId])
            ->andWhere(['not', ['department' => null]])
            ->andWhere(['<>', 'department', ''])
            ->column();

        return $this->render('jobs', [
            'dataProvider' => new ActiveDataProvider([
                'query' => $query,
                'pagination' => ['pageSize' => 20],
            ]),
            'search' => $search,
            'status' => $status,
            'department' => $department,
            'stats' => $stats,
            'departments' => $departments,
            'indexRoute' => $this->indexRoute,
            'jobFormRoute' => $this->jobFormRoute,
        ]);
    }

    public function actionJobForm($id = null)
    {
        $this->requireRecruiter();

        $model = $id ? $this->findOwnedJob((int)$id) : new Job([
            'recruiter_id' => $this->currentRecruiterId(),
            'status' => Job::STATUS_DRAFT,
        ]);

        if (Yii::$app->request->isPost) {
            $action = (string)Yii::$app->request->post('action', 'draft');
            if ($action === 'cancel') {
                return $this->redirect($this->jobsRoute);
            }

            if ($model->load(Yii::$app->request->post())) {
                $model->recruiter_id = $this->currentRecruiterId();

                if ($action === 'publish') {
                    $model->status = Job::STATUS_PUBLISHED;
                } elseif ($action === 'draft') {
                    $model->status = Job::STATUS_DRAFT;
                }
                // action "save": keep existing status (edit without re-publishing)

                if ($model->save()) {
                    $flash = match ($action) {
                        'publish' => 'Job published successfully.',
                        'save' => 'Job updated successfully.',
                        default => $model->status === Job::STATUS_PUBLISHED
                            ? 'Job saved successfully.'
                            : 'Draft saved successfully.',
                    };
                    Yii::$app->session->setFlash('success', $flash);

                    return $this->redirect($this->jobsRoute);
                }
            }
        }

        return $this->render('job-form', [
            'model' => $model,
            'jobsRoute' => $this->jobsRoute,
            'indexRoute' => $this->indexRoute,
            'departmentOptions' => $this->departmentOptions(),
        ]);
    }

    public function actionCloseJob($id)
    {
        $this->requireRecruiter();
        $model = $this->findOwnedJob((int)$id);
        $model->status = Job::STATUS_CLOSED;
        $model->save(false);
        $closedApplications = JobApplication::closeActiveApplicationsForJob((int)$model->job_id);
        $message = 'Job closed.';
        if ($closedApplications > 0) {
            $message .= ' ' . $closedApplications . ' application(s) marked as Closed.';
        }
        Yii::$app->session->setFlash('success', $message);

        return $this->redirect($this->jobsRoute);
    }

    public function actionOpenJob($id)
    {
        $this->requireRecruiter();
        $model = $this->findOwnedJob((int)$id);
        if ((string)$model->status !== Job::STATUS_CLOSED) {
            Yii::$app->session->setFlash('error', 'Only closed jobs can be reopened.');
            return $this->redirect($this->jobsRoute);
        }

        $model->status = Job::STATUS_PUBLISHED;
        $model->save(false);
        Yii::$app->session->setFlash('success', 'Job reopened and is visible to applicants again.');

        return $this->redirect($this->jobsRoute);
    }

    public function actionDeleteJob($id)
    {
        $this->requireRecruiter();
        $this->findOwnedJob((int)$id)->delete();
        Yii::$app->session->setFlash('success', 'Job deleted.');

        return $this->redirect($this->jobsRoute);
    }

    public function actionApplications()
    {
        $this->requireRecruiter();

        $filter = (string)Yii::$app->request->get('filter', 'All');
        $search = trim((string)Yii::$app->request->get('q', ''));
        $selectedId = (int)Yii::$app->request->get('id', 0);

        if (Yii::$app->request->isPost) {
            $application = $this->findOwnedApplication((int)Yii::$app->request->post('application_id'));
            $postAction = (string)Yii::$app->request->post('action', '');
            $selectedId = (int)$application->application_id;

            if ($postAction === 'update_status') {
                $newStatus = (string)Yii::$app->request->post('status', JobApplication::STATUS_NEW);
                if (array_key_exists($newStatus, JobApplication::statusOptions())) {
                    $application->application_status = $newStatus;
                    $application->save(false);
                    Yii::$app->session->setFlash('success', 'Application status updated.');
                }
            } elseif ($postAction === 'schedule_interview') {
                $date = Yii::$app->request->post('interview_date');
                $time = Yii::$app->request->post('interview_time');
                $application->interview_at = $date && $time ? $date . ' ' . $time . ':00' : null;
                $application->interview_type = (string)Yii::$app->request->post('interview_type', 'Google Meet');
                $application->application_status = JobApplication::STATUS_INTERVIEWING;
                if ($application->save()) {
                    Yii::$app->session->setFlash('success', 'Interview scheduled.');
                }
            } elseif ($postAction === 'save_feedback') {
                $rating = Yii::$app->request->post('rating');
                $application->feedback_rating = ($rating !== null && $rating !== '') ? (int)$rating : null;
                $notes = trim((string)Yii::$app->request->post('notes', ''));
                $application->feedback_notes = $notes !== '' ? $notes : null;
                if ($application->save(false)) {
                    Yii::$app->session->setFlash('success', 'Interview feedback saved.');
                } else {
                    Yii::$app->session->setFlash('error', 'Could not save interview feedback.');
                }
            }

            return $this->redirect(array_merge($this->applicationsRoute, [
                'id' => $selectedId,
                'filter' => $filter,
                'q' => $search,
            ]));
        }

        $query = JobApplication::find()
            ->alias('a')
            ->joinWith(['job j', 'applicant u'])
            ->where(['j.recruiter_id' => $this->currentRecruiterId()])
            ->orderBy(['a.applied_at' => SORT_DESC, 'a.application_id' => SORT_DESC]);

        if ($filter !== 'All' && array_key_exists($filter, JobApplication::statusOptions())) {
            $query->andWhere(['a.application_status' => $filter]);
        }

        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'u.full_name', $search],
                ['like', 'u.email', $search],
                ['like', 'u.headline', $search],
                ['like', 'j.job_title', $search],
            ]);
        }

        $applications = $query->limit(200)->all();

        if ($selectedId === 0 && $applications !== []) {
            $selectedId = (int)$applications[0]->application_id;
        }

        $selected = null;
        if ($selectedId > 0) {
            foreach ($applications as $app) {
                if ((int)$app->application_id === $selectedId) {
                    $selected = $app;
                    break;
                }
            }
            if ($selected === null) {
                $selected = $this->findOwnedApplication($selectedId);
                $selected = JobApplication::find()
                    ->alias('a')
                    ->joinWith(['job j', 'applicant u'])
                    ->where(['a.application_id' => $selectedId, 'j.recruiter_id' => $this->currentRecruiterId()])
                    ->one();
            }
        }

        $newCount = (int)JobApplication::find()
            ->alias('a')
            ->joinWith(['job j'])
            ->where(['j.recruiter_id' => $this->currentRecruiterId(), 'a.application_status' => [JobApplication::STATUS_PENDING, 'New']])
            ->count();

        return $this->render('applications', [
            'applications' => $applications,
            'selected' => $selected,
            'filter' => $filter,
            'search' => $search,
            'selectedId' => $selectedId,
            'newCount' => $newCount,
            'indexRoute' => $this->indexRoute,
            'applicationsRoute' => $this->applicationsRoute,
        ]);
    }

    public function actionApplicationStatus($id = 0)
    {
        $this->requireRecruiter();

        $applicationId = (int)$id;
        if ($applicationId <= 0) {
            return $this->render('application-status', [
                'applications' => [],
                'highlightId' => 0,
                'indexRoute' => $this->indexRoute,
                'applicationsRoute' => $this->applicationsRoute,
            ]);
        }

        $application = JobApplication::find()
            ->alias('a')
            ->joinWith(['job j', 'applicant u'])
            ->where(['a.application_id' => $applicationId, 'j.recruiter_id' => $this->currentRecruiterId()])
            ->one();

        if ($application === null) {
            throw new NotFoundHttpException('Application not found.');
        }

        $allForApplicant = JobApplication::find()
            ->alias('a')
            ->joinWith(['job j', 'applicant u'])
            ->where([
                'a.applicant_id' => $application->applicant_id,
                'j.recruiter_id' => $this->currentRecruiterId(),
            ])
            ->orderBy(['a.applied_at' => SORT_DESC])
            ->all();

        return $this->render('application-status', [
            'applications' => $allForApplicant,
            'highlightId' => (int)$application->application_id,
            'indexRoute' => $this->indexRoute,
            'applicationsRoute' => $this->applicationsRoute,
        ]);
    }

    public function actionReports()
    {
        $this->requireRecruiter();

        $jobId = (int)Yii::$app->request->get('job_id', 0);
        $days = (int)Yii::$app->request->get('days', 30);
        if (!in_array($days, [30, 90, 365], true)) {
            $days = 30;
        }

        $report = (new RecruitmentReportService())->build($this->currentRecruiterId(), $jobId ?: null, $days);

        return $this->render('reports', [
            'report' => $report,
            'jobId' => $jobId,
            'days' => $days,
            'indexRoute' => $this->indexRoute,
            'reportsRoute' => $this->reportsRoute,
            'applicationsRoute' => $this->applicationsRoute,
        ]);
    }

    public function actionExportReport()
    {
        $this->requireRecruiter();

        $jobId = (int)Yii::$app->request->get('job_id', 0);
        $days = (int)Yii::$app->request->get('days', 30);
        $report = (new RecruitmentReportService())->build($this->currentRecruiterId(), $jobId ?: null, $days);

        $lines = ["Applicant Name,Email,Job Title,Department,Status,Applied Date"];
        foreach ($report['applicants'] as $row) {
            $lines[] = sprintf(
                '%s,%s,%s,%s,%s,%s',
                $this->csvEscape((string)($row['full_name'] ?? '')),
                $this->csvEscape((string)($row['email'] ?? '')),
                $this->csvEscape((string)($row['job_title'] ?? '')),
                $this->csvEscape((string)($row['department'] ?? '')),
                $this->csvEscape((string)($row['application_status'] ?? '')),
                $this->csvEscape((string)($row['applied_at'] ?? ''))
            );
        }

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="recruitment-report.csv"');
        $response->content = implode("\n", $lines);

        return $response;
    }

    public function actionDownloadApplicantResume($id)
    {
        $this->requireRecruiter();

        $application = JobApplication::find()
            ->alias('a')
            ->joinWith(['job j', 'applicant u'])
            ->where([
                'a.application_id' => (int)$id,
                'j.recruiter_id' => $this->currentRecruiterId(),
            ])
            ->one();

        if ($application === null) {
            throw new NotFoundHttpException('Application not found.');
        }

        $applicant = $application->applicant;
        if ($applicant === null || trim((string)$applicant->resume_path) === '') {
            throw new NotFoundHttpException('Resume not found.');
        }

        $path = Yii::getAlias('@frontend/web/' . ltrim((string)$applicant->resume_path, '/'));
        if (!is_file($path)) {
            throw new NotFoundHttpException('Resume file not found.');
        }

        $downloadName = $applicant->resume_original_name ?: basename($path);
        $inline = (bool)Yii::$app->request->get('inline', 0);

        return Yii::$app->response->sendFile($path, $downloadName, ['inline' => $inline]);
    }

    public function actionConvertApplicant($id)
    {
        $this->requireRecruiter();
        $application = $this->findOwnedApplication((int)$id);
        $applicant = $application->applicant;
        $job = $application->job;

        if ($applicant === null || $job === null) {
            throw new NotFoundHttpException('Application not found.');
        }

        $department = $this->resolveDepartment($job->department);
        if ($department === null) {
            Yii::$app->session->setFlash('error', 'Create at least one HR department before converting applicants.');
            return $this->redirect(array_merge($this->applicationsRoute, ['id' => (int)$application->application_id]));
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

        return $this->redirect(array_merge($this->applicationsRoute, ['id' => (int)$application->application_id]));
    }

    protected function findOwnedJob(int $id): Job
    {
        $model = Job::findOne(['job_id' => $id, 'recruiter_id' => $this->currentRecruiterId()]);
        if ($model === null) {
            throw new NotFoundHttpException('Job not found.');
        }

        return $model;
    }

    protected function findOwnedApplication(int $id): JobApplication
    {
        $model = JobApplication::find()
            ->alias('a')
            ->joinWith(['job j'])
            ->where(['a.application_id' => $id, 'j.recruiter_id' => $this->currentRecruiterId()])
            ->one();
        if ($model === null) {
            throw new NotFoundHttpException('Application not found.');
        }

        return $model;
    }

    /** @return string[] */
    protected function departmentOptions(): array
    {
        $fromHr = Department::find()->select('name')->orderBy(['name' => SORT_ASC])->column();
        $legacy = [
            'School of Life Sciences',
            'Engineering Department',
            'Administration',
            'Human Resources',
        ];

        return array_values(array_unique(array_merge($fromHr, $legacy)));
    }

    protected function resolveDepartment(?string $name): ?Department
    {
        $name = trim((string)$name);
        if ($name !== '') {
            $department = Department::find()->where(['name' => $name])->one();
            if ($department !== null) {
                return $department;
            }
        }

        return Department::find()->orderBy(['id' => SORT_ASC])->one();
    }

    protected function csvEscape(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }
}
