<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent $model
 * @var frontend\modules\hr\models\StudentPortalForm $portalForm
 * @var bool $isEdit
 * @var common\modules\employee\models\SchoolDepartment[] $departments
 * @var array<int, array<int, array{id:int,name:string}>> $gradesByDepartment
 * @var int $selectedDepartmentId
 * @var int $selectedGradeId
 * @var array<int, array{id:int,label:string,email:string,phone:string,emergency_phone:string,guardian_name:string,children:array<int, array{id:int,label:string,first_name:string,father_name:string,grandfather_name:string,family_name:string}>}> $financeParentOptions
 * @var array<int, string> $residenceAreaOptions
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\employee\models\SchoolStudent;
use common\modules\employee\services\StudentPortalEmailService;
use frontend\modules\hr\models\StudentPortalForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = ($isEdit ? 'Edit Student Profile' : 'Student Registry') . ' | CultureHR';

$residenceAreaOptions = $residenceAreaOptions ?? [];
$canSelectResidenceArea = SchoolStudent::hasColumn('residence_area_id');
$canEditAddressDetails = SchoolStudent::hasColumn('home_address');

$birthDateMin = (new \DateTimeImmutable('today'))
    ->sub(new \DateInterval('P' . SchoolStudent::MAX_STUDENT_AGE_YEARS . 'Y'))
    ->format('Y-m-d');
$birthDateMax = (new \DateTimeImmutable('today'))
    ->sub(new \DateInterval('P' . SchoolStudent::MIN_ENROLLMENT_AGE_YEARS . 'Y'))
    ->format('Y-m-d');

$departmentOptions = ArrayHelper::map($departments, 'id', 'name');
$req = static fn() => Html::tag('span', '*', ['style' => 'color:#ef4444;']);

$this->registerCss(<<<'CSS'
.enrollment-shell { background:#f1f5f9; }
.enrollment-wrap { max-width:72rem; margin:0 auto; padding:1.5rem 1.5rem 2.5rem; }
.enrollment-stack > * + * { margin-top:1.5rem; }
.enrollment-fields > * + * { margin-top:1rem; }
.enrollment-back { display:inline-flex; align-items:center; gap:.5rem; font-size:.68rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:#64748b; text-decoration:none; }
.enrollment-back:hover { color:#1e3a8a; }
.enrollment-hero { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1.25rem; }
.enrollment-hero h1 { margin:0; font-size:1.875rem; line-height:1.2; font-weight:800; color:#0f172a; letter-spacing:-.02em; }
.enrollment-hero p { margin:.5rem 0 0; color:#64748b; font-size:.95rem; }
.enrollment-status { width:12rem; flex-shrink:0; }
.enrollment-card { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; box-shadow:0 8px 30px rgba(15,23,42,.05); overflow:hidden; }
.enrollment-card-head { display:flex; align-items:center; gap:.75rem; padding:1rem 1.25rem; border-bottom:1px solid #f1f5f9; }
.enrollment-card-head h2 { margin:0; font-size:.95rem; font-weight:800; color:#1e3a8a; }
.enrollment-card-icon { width:2rem; height:2rem; border-radius:.55rem; background:#eff6ff; color:#1e3a8a; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
.enrollment-card-icon .material-symbols-outlined { font-size:1.15rem; }
.enrollment-card-body { padding:1.25rem; }
.enrollment-label { display:block; margin-bottom:.45rem; font-size:.72rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#475569; }
.enrollment-input, .enrollment-select, .enrollment-textarea { width:100%; border:1px solid #e2e8f0; border-radius:.65rem; background:#fff; color:#0f172a; font-size:.875rem; line-height:1.25rem; padding:.65rem .85rem; box-sizing:border-box; }
.enrollment-input:focus, .enrollment-select:focus, .enrollment-textarea:focus { outline:none; border-color:#1e3a8a; box-shadow:0 0 0 3px rgba(30,58,138,.12); }
.enrollment-input[readonly] { background:#f8fafc; color:#334155; cursor:not-allowed; }
.enrollment-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpath d='M4 6l4 4 4-4'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .75rem center; padding-right:2rem; }
.enrollment-textarea { min-height:6.5rem; resize:vertical; }
.enrollment-error { margin-top:.35rem; font-size:.75rem; color:#dc2626; }
.enrollment-row-pair { display:grid; grid-template-columns:1fr; gap:1.5rem; }
.enrollment-grid-2 { display:grid; grid-template-columns:1fr; gap:1rem; }
.enrollment-grid-3 { display:grid; grid-template-columns:1fr; gap:1rem; }
.enrollment-grid-4 { display:grid; grid-template-columns:1fr; gap:1rem; }
.enrollment-span-2 { grid-column:span 1; }
@media (min-width:768px) {
  .enrollment-grid-2 { grid-template-columns:repeat(2,minmax(0,1fr)); }
  .enrollment-grid-3 { grid-template-columns:repeat(3,minmax(0,1fr)); }
  .enrollment-grid-4 { grid-template-columns:repeat(4,minmax(0,1fr)); }
  .enrollment-span-2 { grid-column:span 2; }
}
@media (min-width:1024px) {
  .enrollment-row-pair { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
.enrollment-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:.75rem; padding-top:.25rem; }
.enrollment-btn-secondary { display:inline-flex; align-items:center; justify-content:center; height:2.75rem; padding:0 1.35rem; border:1px solid #cbd5e1; border-radius:.65rem; background:#fff; color:#334155; font-size:.85rem; font-weight:700; text-decoration:none; }
.enrollment-btn-primary { display:inline-flex; align-items:center; justify-content:center; height:2.75rem; padding:0 1.35rem; border:0; border-radius:.65rem; background:#1e3a8a; color:#fff; font-size:.85rem; font-weight:700; cursor:pointer; }
.enrollment-btn-primary:hover { background:#1e40af; }
.enrollment-alert { padding:1rem 1.1rem; border-radius:.85rem; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; font-size:.875rem; }
CSS
);

$gradesByDepartmentJson = json_encode($gradesByDepartment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$selectedDepartmentIdJson = (int)$selectedDepartmentId;
$selectedGradeIdJson = (int)$selectedGradeId;
$financeParentOptions = $financeParentOptions ?? [];
$financeParentOptionsJson = Json::encode($financeParentOptions);
$enrollmentFieldIds = [
    'first_name' => Html::getInputId($model, 'first_name'),
    'father_name' => Html::getInputId($model, 'father_name'),
    'grandfather_name' => Html::getInputId($model, 'grandfather_name'),
    'family_name' => Html::getInputId($model, 'family_name'),
    'full_name' => Html::getInputId($model, 'full_name'),
    'email' => Html::getInputId($model, 'email'),
    'phone' => Html::getInputId($model, 'phone'),
    'home_address' => Html::getInputId($model, 'home_address'),
    'guardian_name' => Html::getInputId($model, 'guardian_name'),
    'guardian_phone' => Html::getInputId($model, 'guardian_phone'),
    'emergency_contact' => Html::getInputId($model, 'emergency_contact'),
];
$enrollmentFieldIdsJson = Json::encode($enrollmentFieldIds);
$portalEmailFieldId = Html::getInputId($portalForm, 'portal_email');
$portalEmailDomainJson = Json::encode((new StudentPortalEmailService())->getDomain());
$autoGeneratePortalEmailJson = $isEdit ? 'false' : 'true';

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'student_affairs']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Student Affairs', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto enrollment-shell">
            <div class="enrollment-wrap enrollment-stack">
                <a href="<?= Html::encode(Url::to(['/hr/student-affairs/students'])) ?>" class="enrollment-back">
                    <span class="material-symbols-outlined" style="font-size:1rem;">arrow_back</span>
                    Back to Student Registry
                </a>

                <div class="enrollment-hero">
                    <div>
                        <h1>Student Registry</h1>
                        <p>Manage comprehensive student data, academic records, and guardianship details.</p>
                    </div>
                    <div class="enrollment-status">
                        <label class="enrollment-label">Profile Status <?= $req() ?></label>
                        <?= Html::activeDropDownList($model, 'status', SchoolStudent::statusOptions(), [
                            'class' => 'enrollment-select',
                        ]) ?>
                    </div>
                </div>

                <?php if ($model->hasErrors() || $portalForm->hasErrors()): ?>
                    <div class="enrollment-alert">
                        <?= Html::errorSummary($model, ['header' => 'Please fix the following:']) ?>
                        <?= Html::errorSummary($portalForm, ['header' => '']) ?>
                    </div>
                <?php endif; ?>

                <?= Html::beginForm($isEdit ? ['update', 'id' => (int)$model->id] : ['create'], 'post', [
                    'id' => 'student-enrollment-form',
                    'class' => 'enrollment-stack',
                ]) ?>

                <?php if (!$isEdit && $financeParentOptions !== []): ?>
                    <section class="enrollment-card">
                        <div class="enrollment-card-head">
                            <span class="enrollment-card-icon"><span class="material-symbols-outlined">family_restroom</span></span>
                            <h2>Link Registered Parent</h2>
                        </div>
                        <div class="enrollment-card-body enrollment-grid-2">
                            <input type="hidden" name="finance_parent_child_id" id="enrollment-finance-child-hidden" value="">
                            <div>
                                <label class="enrollment-label" for="enrollment-finance-parent-id">Choose Parent</label>
                                <select id="enrollment-finance-parent-id" class="enrollment-select">
                                    <option value="">Select parent...</option>
                                    <?php foreach ($financeParentOptions as $parentOption): ?>
                                        <option value="<?= (int)$parentOption['id'] ?>">
                                            <?= Html::encode($parentOption['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="enrollment-label" for="enrollment-finance-child-id">Choose Child</label>
                                <select id="enrollment-finance-child-id" class="enrollment-select" disabled>
                                    <option value="">Select child...</option>
                                </select>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="enrollment-card">
                        <div class="enrollment-card-head">
                            <span class="enrollment-card-icon"><span class="material-symbols-outlined">person</span></span>
                            <h2>Personal Information</h2>
                        </div>
                        <div class="enrollment-card-body enrollment-fields">
                            <?php if (SchoolStudent::hasNameParts()): ?>
                                <div class="enrollment-grid-4">
                                    <div>
                                        <label class="enrollment-label">First Name <?= $req() ?></label>
                                        <?= Html::activeTextInput($model, 'first_name', ['class' => 'enrollment-input', 'required' => true]) ?>
                                        <?= Html::error($model, 'first_name', ['class' => 'enrollment-error']) ?>
                                    </div>
                                    <div>
                                        <label class="enrollment-label">Father Name <?= $req() ?></label>
                                        <?= Html::activeTextInput($model, 'father_name', ['class' => 'enrollment-input', 'required' => true]) ?>
                                        <?= Html::error($model, 'father_name', ['class' => 'enrollment-error']) ?>
                                    </div>
                                    <div>
                                        <label class="enrollment-label">Grandfather Name <?= $req() ?></label>
                                        <?= Html::activeTextInput($model, 'grandfather_name', ['class' => 'enrollment-input', 'required' => true]) ?>
                                        <?= Html::error($model, 'grandfather_name', ['class' => 'enrollment-error']) ?>
                                    </div>
                                    <div>
                                        <label class="enrollment-label">Family Name <?= $req() ?></label>
                                        <?= Html::activeTextInput($model, 'family_name', ['class' => 'enrollment-input', 'required' => true]) ?>
                                        <?= Html::error($model, 'family_name', ['class' => 'enrollment-error']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="enrollment-grid-2">
                            <?php if (!SchoolStudent::hasNameParts()): ?>
                                <div>
                                    <label class="enrollment-label">Full Name <?= $req() ?></label>
                                    <?= Html::activeTextInput($model, 'full_name', ['class' => 'enrollment-input', 'placeholder' => 'e.g. Jonathan Doe', 'required' => true]) ?>
                                    <?= Html::error($model, 'full_name', ['class' => 'enrollment-error']) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <label class="enrollment-label">Nationality <?= $req() ?></label>
                                <?= Html::activeTextInput($model, 'nationality', ['class' => 'enrollment-input', 'placeholder' => 'e.g. American', 'required' => true]) ?>
                                <?= Html::error($model, 'nationality', ['class' => 'enrollment-error']) ?>
                            </div>
                            <div>
                                <label class="enrollment-label">Gender <?= $req() ?></label>
                                <?= Html::activeDropDownList($model, 'gender', ['' => 'Select Gender'] + SchoolStudent::genderOptions(), ['class' => 'enrollment-select', 'required' => true]) ?>
                                <?= Html::error($model, 'gender', ['class' => 'enrollment-error']) ?>
                            </div>
                            <div>
                                <label class="enrollment-label">Date of Birth <?= $req() ?></label>
                                <?= Html::activeInput('date', $model, 'birth_date', [
                                    'class' => 'enrollment-input',
                                    'required' => true,
                                    'min' => $birthDateMin,
                                    'max' => $birthDateMax,
                                ]) ?>
                                <?= Html::error($model, 'birth_date', ['class' => 'enrollment-error']) ?>
                            </div>
                            <div>
                                <label class="enrollment-label">Blood Group <?= $req() ?></label>
                                <?= Html::activeDropDownList($model, 'blood_group', ['' => 'Select Blood Group'] + SchoolStudent::bloodGroupOptions(), ['class' => 'enrollment-select', 'required' => true]) ?>
                                <?= Html::error($model, 'blood_group', ['class' => 'enrollment-error']) ?>
                            </div>
                            <div>
                                <label class="enrollment-label">Religion <?= $req() ?></label>
                                <?= Html::activeDropDownList($model, 'religion', ['' => 'Select Religion'] + SchoolStudent::religionOptions(), ['class' => 'enrollment-select', 'required' => true]) ?>
                                <?= Html::error($model, 'religion', ['class' => 'enrollment-error']) ?>
                            </div>
                            <?php if ($isEdit): ?>
                                <div>
                                    <label class="enrollment-label">Registration #</label>
                                    <?= Html::activeTextInput($model, 'student_code', ['class' => 'enrollment-input']) ?>
                                </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    </section>

                <section class="enrollment-card">
                    <div class="enrollment-card-head">
                        <span class="enrollment-card-icon"><span class="material-symbols-outlined">contact_page</span></span>
                        <h2>Contact Information</h2>
                    </div>
                    <div class="enrollment-card-body enrollment-fields">
                        <div class="enrollment-grid-3">
                            <div>
                                <label class="enrollment-label">Parent Phone Number <?= $req() ?></label>
                                <?= Html::activeTextInput($model, 'phone', [
                                    'class' => 'enrollment-input',
                                    'placeholder' => '0791234567',
                                    'required' => true,
                                    'data-digits-only' => true,
                                    'inputmode' => 'numeric',
                                    'pattern' => '[0-9]*',
                                ]) ?>
                                <?= Html::error($model, 'phone', ['class' => 'enrollment-error']) ?>
                            </div>
                            <?php if ($canSelectResidenceArea): ?>
                                <div>
                                    <label class="enrollment-label" for="<?= Html::getInputId($model, 'residence_area_id') ?>">Area of Residence</label>
                                    <?= Html::activeDropDownList(
                                        $model,
                                        'residence_area_id',
                                        $residenceAreaOptions,
                                        [
                                            'class' => 'enrollment-input',
                                            'prompt' => $residenceAreaOptions === [] ? 'No areas available' : 'Select area of residence',
                                            'disabled' => $residenceAreaOptions === [],
                                        ]
                                    ) ?>
                                    <?= Html::error($model, 'residence_area_id', ['class' => 'enrollment-error']) ?>
                                    <?php if ($residenceAreaOptions === []): ?>
                                        <p class="enrollment-error" style="margin-top:.35rem;">Add areas under Transportations → Areas first.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($canEditAddressDetails): ?>
                            <div>
                                <label class="enrollment-label" for="<?= Html::getInputId($model, 'home_address') ?>">Address Details</label>
                                <?= Html::activeTextarea($model, 'home_address', [
                                    'class' => 'enrollment-input',
                                    'rows' => 3,
                                    'maxlength' => 100,
                                    'placeholder' => 'Street, building, landmark, or other address details',
                                ]) ?>
                                <?= Html::error($model, 'home_address', ['class' => 'enrollment-error']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if (SchoolStudent::hasColumn('user_id')): ?>
                    <section class="enrollment-card">
                        <div class="enrollment-card-head">
                            <span class="enrollment-card-icon"><span class="material-symbols-outlined">lock</span></span>
                            <h2>Portal Credentials</h2>
                        </div>
                        <div class="enrollment-card-body enrollment-fields">
                            <p style="margin:0;font-size:.8rem;color:#64748b;">
                                <?php if ($isEdit): ?>
                                    Portal email and password are the credentials the student uses to access the LMS portal.
                                <?php else: ?>
                                    Portal email is generated automatically from the student name and national ID. The password is generated automatically, the same way parent portal passwords are created.
                                <?php endif; ?>
                            </p>
                            <div class="enrollment-grid-2">
                                <div>
                                    <label class="enrollment-label">Portal Email <?= $req() ?></label>
                                    <?= Html::activeInput('email', $portalForm, 'portal_email', [
                                        'class' => 'enrollment-input',
                                        'placeholder' => 'first.father.family.12@students.culturehr.local',
                                        'required' => true,
                                        'readonly' => !$isEdit,
                                    ]) ?>
                                    <?php if (!$isEdit): ?>
                                        <p class="enrollment-error" style="color:#64748b;margin-top:.35rem;">
                                            Format: first.father.family.{id}@<?= Html::encode((new StudentPortalEmailService())->getDomain()) ?>
                                            (student ID is added automatically when you save)
                                        </p>
                                    <?php endif; ?>
                                    <?= Html::error($portalForm, 'portal_email', ['class' => 'enrollment-error']) ?>
                                </div>
                                <div>
                                    <label class="enrollment-label" for="student-portal-password">Portal Password</label>
                                    <div class="flex items-center gap-2 w-full">
                                        <?= Html::activePasswordInput($portalForm, 'portal_password', [
                                            'id' => 'student-portal-password',
                                            'class' => 'enrollment-input flex-1 min-w-0' . (!$isEdit ? ' bg-slate-50' : ''),
                                            'readonly' => !$isEdit,
                                            'required' => false,
                                            'autocomplete' => 'new-password',
                                        ]) ?>
                                        <button type="button"
                                                data-password-toggle="student-portal-password"
                                                class="shrink-0 inline-flex items-center justify-center p-1 text-slate-500 hover:text-[#1e3a8a] bg-transparent border-0 cursor-pointer"
                                                aria-label="Show password">
                                            <span class="material-symbols-outlined text-2xl leading-none">visibility</span>
                                        </button>
                                    </div>
                                    <p class="enrollment-error" style="color:#64748b;margin-top:.35rem;">
                                        <?php if (!$isEdit): ?>
                                            Auto-generated. You can view it here; it will be saved when you create the student.
                                        <?php elseif (trim((string)$portalForm->portal_password) !== ''): ?>
                                            You can view or change this password. Leave unchanged to keep the current one.
                                        <?php else: ?>
                                            Password was changed by the student or is unavailable. A new one was generated so you can save it.
                                        <?php endif; ?>
                                    </p>
                                    <?= Html::error($portalForm, 'portal_password', ['class' => 'enrollment-error']) ?>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <div class="enrollment-row-pair">
                    <section class="enrollment-card">
                        <div class="enrollment-card-head">
                            <span class="enrollment-card-icon"><span class="material-symbols-outlined">family_restroom</span></span>
                            <h2>Parent / Guardian</h2>
                        </div>
                        <div class="enrollment-card-body enrollment-fields">
                            <div>
                                <label class="enrollment-label">Guardian Name <?= $req() ?></label>
                                <?= Html::activeTextInput($model, 'guardian_name', ['class' => 'enrollment-input', 'required' => true]) ?>
                                <?= Html::error($model, 'guardian_name', ['class' => 'enrollment-error']) ?>
                            </div>
                            <div class="enrollment-grid-2">
                                <div>
                                    <label class="enrollment-label">Contact Phone <?= $req() ?></label>
                                    <?= Html::activeTextInput($model, 'guardian_phone', [
                                        'class' => 'enrollment-input',
                                        'required' => true,
                                        'data-digits-only' => true,
                                        'inputmode' => 'numeric',
                                        'pattern' => '[0-9]*',
                                    ]) ?>
                                    <?= Html::error($model, 'guardian_phone', ['class' => 'enrollment-error']) ?>
                                </div>
                                <div>
                                    <label class="enrollment-label">Emergency Contact <?= $req() ?></label>
                                    <?= Html::activeTextInput($model, 'emergency_contact', [
                                        'class' => 'enrollment-input',
                                        'required' => true,
                                        'data-digits-only' => true,
                                        'inputmode' => 'numeric',
                                        'pattern' => '[0-9]*',
                                    ]) ?>
                                    <?= Html::error($model, 'emergency_contact', ['class' => 'enrollment-error']) ?>
                                </div>
                            </div>
                            <div>
                                <label class="enrollment-label">Contact Email</label>
                                <?= Html::activeInput('email', $model, 'email', ['class' => 'enrollment-input', 'placeholder' => 'parent@example.com']) ?>
                                <?= Html::error($model, 'email', ['class' => 'enrollment-error']) ?>
                            </div>
                        </div>
                    </section>

                    <section class="enrollment-card">
                        <div class="enrollment-card-head">
                            <span class="enrollment-card-icon"><span class="material-symbols-outlined">school</span></span>
                            <h2>Academic Assignment</h2>
                        </div>
                        <div class="enrollment-card-body enrollment-fields">
                            <div class="enrollment-grid-2">
                                <div>
                                    <label class="enrollment-label">Department <?= $req() ?></label>
                                    <?= Html::dropDownList('enrollment_department_id', $selectedDepartmentId ?: '', ['' => 'Select Department'] + $departmentOptions, [
                                        'class' => 'enrollment-select',
                                        'id' => 'enrollment-department-id',
                                        'required' => true,
                                    ]) ?>
                                </div>
                                <div>
                                    <label class="enrollment-label">Grade Level <?= $req() ?></label>
                                    <?= Html::dropDownList('enrollment_grade_id', $selectedGradeId ?: '', ['' => 'Select Grade'], [
                                        'class' => 'enrollment-select',
                                        'id' => 'enrollment-grade-id',
                                        'required' => true,
                                    ]) ?>
                                    <?= Html::error($model, 'school_grade_id', ['class' => 'enrollment-error']) ?>
                                </div>
                            </div>
                            <div>
                                <label class="enrollment-label">Academic Year <?= $req() ?></label>
                                <?= Html::activeTextInput($model, 'academic_year', [
                                    'class' => 'enrollment-input',
                                    'placeholder' => SchoolStudent::defaultAcademicYear(),
                                    'required' => true,
                                ]) ?>
                                <?= Html::error($model, 'academic_year', ['class' => 'enrollment-error']) ?>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="enrollment-card">
                    <div class="enrollment-card-head">
                        <span class="enrollment-card-icon"><span class="material-symbols-outlined">medical_information</span></span>
                        <h2>Medical Information</h2>
                    </div>
                    <div class="enrollment-card-body enrollment-fields">
                        <div>
                            <label class="enrollment-label">Medical Notes / Allergies <?= $req() ?></label>
                            <?= Html::activeTextarea($model, 'medical_notes', [
                                'rows' => 4,
                                'class' => 'enrollment-textarea',
                                'maxlength' => 250,
                                'placeholder' => 'List any chronic conditions or allergies...',
                                'required' => true,
                            ]) ?>
                            <?= Html::error($model, 'medical_notes', ['class' => 'enrollment-error']) ?>
                        </div>
                    </div>
                </section>

                <div class="enrollment-actions">
                    <a href="<?= Html::encode(Url::to(['/hr/student-affairs/students'])) ?>" class="enrollment-btn-secondary">
                        Cancel Changes
                    </a>
                    <button type="submit" class="enrollment-btn-primary">
                        Save Student Profile
                    </button>
                </div>

                <?= Html::endForm() ?>
            </div>
        </main>
    </div>
</div>

<?php
$this->registerJs(<<<JS
(function() {
  var gradesByDepartment = {$gradesByDepartmentJson} || {};
  var departmentSelect = document.getElementById('enrollment-department-id');
  var gradeSelect = document.getElementById('enrollment-grade-id');

  function rebuildGrades(departmentId, selectedGradeId) {
    if (!gradeSelect) return;
    departmentId = String(departmentId || '');
    selectedGradeId = String(selectedGradeId || '');
    var items = gradesByDepartment[departmentId] || gradesByDepartment[parseInt(departmentId, 10)] || [];
    gradeSelect.innerHTML = '<option value="">Select Grade</option>';
    items.forEach(function(item) {
      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = String(item.name);
      if (selectedGradeId && String(item.id) === selectedGradeId) {
        option.selected = true;
      }
      gradeSelect.appendChild(option);
    });
  }

  if (departmentSelect && gradeSelect) {
    rebuildGrades({$selectedDepartmentIdJson}, {$selectedGradeIdJson});
    departmentSelect.addEventListener('change', function() {
      rebuildGrades(this.value, '');
    });
  }

  var financeParents = {$financeParentOptionsJson} || [];
  var enrollmentFieldIds = {$enrollmentFieldIdsJson} || {};
  var parentSelect = document.getElementById('enrollment-finance-parent-id');
  var childSelect = document.getElementById('enrollment-finance-child-id');
  var childHidden = document.getElementById('enrollment-finance-child-hidden');

  function setSelectedFinanceChildId(childId) {
    if (childHidden) {
      childHidden.value = childId ? String(childId) : '';
    }
  }

  function setEnrollmentField(key, value) {
    var fieldId = enrollmentFieldIds[key];
    if (!fieldId) {
      return;
    }
    var field = document.getElementById(fieldId);
    if (field) {
      field.value = value || '';
    }
  }

  function findFinanceParent(parentId) {
    parentId = String(parentId || '');
    for (var i = 0; i < financeParents.length; i += 1) {
      if (String(financeParents[i].id) === parentId) {
        return financeParents[i];
      }
    }
    return null;
  }

  function rebuildFinanceChildren(parentId, selectedChildId) {
    if (!childSelect) {
      return;
    }

    childSelect.innerHTML = '<option value="">Select child...</option>';
    childSelect.disabled = true;
    setSelectedFinanceChildId('');

    var parent = findFinanceParent(parentId);
    if (!parent || !parent.children || !parent.children.length) {
      return;
    }

    parent.children.forEach(function(child) {
      var option = document.createElement('option');
      option.value = String(child.id);
      option.textContent = String(child.label || '');
      if (selectedChildId && String(child.id) === String(selectedChildId)) {
        option.selected = true;
      }
      childSelect.appendChild(option);
    });
    childSelect.disabled = false;
    if (selectedChildId) {
      setSelectedFinanceChildId(selectedChildId);
    }
  }

  function applyFinanceChildSelection() {
    if (!parentSelect || !childSelect) {
      return;
    }

    var parent = findFinanceParent(parentSelect.value);
    var childId = childSelect.value;
    setSelectedFinanceChildId(childId);
    if (!parent || !childId) {
      return;
    }

    var child = null;
    parent.children.forEach(function(item) {
      if (String(item.id) === String(childId)) {
        child = item;
      }
    });
    if (!child) {
      return;
    }

    setEnrollmentField('first_name', child.first_name);
    setEnrollmentField('father_name', child.father_name);
    setEnrollmentField('grandfather_name', child.grandfather_name);
    setEnrollmentField('family_name', child.family_name);
    setEnrollmentField('full_name', [child.first_name, child.father_name, child.grandfather_name, child.family_name].filter(Boolean).join(' '));
    setEnrollmentField('email', parent.email);
    setEnrollmentField('phone', parent.phone);
    setEnrollmentField('guardian_name', parent.guardian_name);
    setEnrollmentField('guardian_phone', parent.phone);
    setEnrollmentField('emergency_contact', parent.emergency_phone);
    refreshPortalEmail();
  }

  var portalEmailField = document.getElementById('{$portalEmailFieldId}');
  var portalEmailDomain = {$portalEmailDomainJson};
  var autoGeneratePortalEmail = {$autoGeneratePortalEmailJson};
  var portalEmailSourceFields = ['first_name', 'father_name', 'family_name', 'full_name'];

  function slugPortalSegment(value) {
    value = String(value || '').trim().toLowerCase();
    if (!value) {
      return '';
    }
    value = value.replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '').replace(/-+/g, '-');
    return value;
  }

  function getEnrollmentFieldValue(key) {
    var fieldId = enrollmentFieldIds[key];
    if (!fieldId) {
      return '';
    }
    var field = document.getElementById(fieldId);
    return field ? String(field.value || '').trim() : '';
  }

  function buildPortalEmailLocalPart() {
    var segments = [];
    var firstName = slugPortalSegment(getEnrollmentFieldValue('first_name'));
    var fatherName = slugPortalSegment(getEnrollmentFieldValue('father_name'));
    var familyName = slugPortalSegment(getEnrollmentFieldValue('family_name'));
    var fullName = slugPortalSegment(getEnrollmentFieldValue('full_name'));

    if (firstName) segments.push(firstName);
    if (fatherName) segments.push(fatherName);
    if (familyName) segments.push(familyName);
    if (!segments.length && fullName) {
      segments.push(fullName);
    }
    if (!segments.length) {
      segments.push('student');
    }

    // Real student ID is appended on the server after save.
    segments.push('{id}');

    return segments.join('.');
  }

  function refreshPortalEmail() {
    if (!autoGeneratePortalEmail || !portalEmailField) {
      return;
    }
    portalEmailField.value = buildPortalEmailLocalPart() + '@' + portalEmailDomain;
  }

  if (autoGeneratePortalEmail) {
    portalEmailSourceFields.forEach(function(key) {
      var fieldId = enrollmentFieldIds[key];
      var field = fieldId ? document.getElementById(fieldId) : null;
      if (field) {
        field.addEventListener('input', refreshPortalEmail);
        field.addEventListener('change', refreshPortalEmail);
      }
    });
    refreshPortalEmail();
  }

  if (parentSelect && childSelect) {
    parentSelect.addEventListener('change', function() {
      rebuildFinanceChildren(this.value, '');
      childSelect.value = '';
    });
    childSelect.addEventListener('change', applyFinanceChildSelection);
  }

  document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      var input = document.getElementById(button.getAttribute('data-password-toggle'));
      if (!input) {
        return;
      }
      var icon = button.querySelector('.material-symbols-outlined');
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      if (icon) {
        icon.textContent = showing ? 'visibility' : 'visibility_off';
      }
      button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
  });
})();
JS
);
?>
