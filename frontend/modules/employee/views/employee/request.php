<?php

/**
 * @var yii\web\View $this
 * @var string $pageTitle
 * @var string $navLinksHtml
 * @var common\modules\employee\models\Employee $employee
 * @var common\modules\employee\models\EmployeeChangeRequest $model
 * @var common\modules\employee\models\EmployeeChangeRequest[] $recentRequests
 * @var string $successMessage
 * @var string $errorMessage
 * @var string $successVisibleClass
 * @var string $errorVisibleClass
 * @var array $urgencyOptions
 */

use common\modules\employee\models\EmployeeChangeRequest;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = $pageTitle ?? 'Change Request';

$inputClass = 'block w-full h-11 pl-11 pr-4 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#eb652c]/20 focus:border-[#eb652c] transition-colors';
$inputReadonlyClass = $inputClass . ' bg-slate-50 text-slate-700 cursor-default focus:ring-0 focus:border-slate-300';
$selectClass = 'block w-full h-11 pl-3 pr-9 rounded-lg border border-slate-300 bg-white text-slate-900 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#eb652c]/20 focus:border-[#eb652c] transition-colors';
$textareaClass = 'block w-full min-h-[220px] lg:min-h-[280px] rounded-lg border border-slate-300 bg-white text-slate-900 text-sm leading-relaxed placeholder:text-slate-400 px-4 py-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#eb652c]/20 focus:border-[#eb652c] transition-colors resize-y';
$sectionTitleClass = 'text-[0.6875rem] font-bold uppercase tracking-[0.14em] text-slate-400';
$fieldGroupClass = 'flex flex-col gap-1.5 min-w-0';
$labelClass = 'text-sm font-semibold text-slate-800';
$hintClass = 'text-xs text-slate-500 leading-snug mt-0.5';
$iconClass = 'material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none select-none';

$statusPill = static function (EmployeeChangeRequest $request): array {
    return match (strtolower(trim((string)$request->status))) {
        'approved' => ['bg-emerald-100 text-emerald-700', 'Approved'],
        'rejected' => ['bg-red-100 text-red-700', 'Rejected'],
        default => ['bg-amber-100 text-amber-800', 'Pending'],
    };
};

?>

<?= $this->render('../layouts/_shell-start', [
    'pageTitle' => $this->title,
    'currentPage' => 'my_profile',
    'userName' => $employee->full_name,
    'userRole' => $employee->job_title ?: 'Employee',
]) ?>

<div class="flex w-full min-h-[calc(100dvh-5rem)] xl:min-h-[calc(100dvh-5rem)]">
    <aside class="hidden xl:flex xl:w-[340px] 2xl:w-[380px] shrink-0 flex-col border-r border-slate-200 bg-white overflow-hidden">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-[#642754] text-xl">history</span>
                Your requests
            </h2>
            <p class="text-xs text-slate-500 mt-1">Track submissions sent to HR.</p>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <?php if (empty($recentRequests)): ?>
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center">
                    <span class="material-symbols-outlined text-4xl text-slate-300">inbox</span>
                    <p class="text-sm font-semibold text-slate-600 mt-2">No requests yet</p>
                    <p class="text-xs text-slate-500 mt-1">Your first submission will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentRequests as $request):
                    [$pillClass, $pillLabel] = $statusPill($request);
                    $submitted = !empty($request->created_at)
                        ? date('M j, Y', strtotime((string)$request->created_at))
                        : '';
                    ?>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-bold text-slate-500">#<?= (int)$request->id ?></span>
                            <div class="flex gap-1">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= ($request->urgency ?? '') === 'Urgent' ? 'bg-red-100 text-red-700' : 'bg-slate-200 text-slate-600' ?>">
                                    <?= Html::encode($request->urgency ?? 'Normal') ?>
                                </span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= $pillClass ?>">
                                    <?= Html::encode($pillLabel) ?>
                                </span>
                            </div>
                        </div>
                        <p class="text-sm font-semibold text-slate-800 line-clamp-2 leading-snug">
                            <?= Html::encode($request->getDisplayTitle()) ?>
                        </p>
                        <?php if ($submitted !== ''): ?>
                            <p class="text-[11px] text-slate-400 mt-2 font-medium"><?= Html::encode($submitted) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="p-6 border-t border-slate-200 bg-slate-50/50">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Writing tips</h3>
            <ul class="text-xs text-slate-600 space-y-2 leading-relaxed list-disc pl-4">
                <li>Use one field per line, e.g. <code class="text-[11px] bg-white px-1 rounded">current department: Sales</code></li>
                <li>Add the new value as <code class="text-[11px] bg-white px-1 rounded">new department: Engineering</code></li>
                <li>HR sees a side-by-side comparison of your request.</li>
            </ul>
        </div>
    </aside>

    <div class="flex-1 overflow-y-auto min-w-0">
        <div class="max-w-[1200px] mx-auto w-full px-6 sm:px-8 lg:px-10 py-8 lg:py-10">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
                <div>
                    <a href="<?= Url::to(['/employee/employee/profile']) ?>"
                       class="inline-flex items-center gap-1 text-sm font-semibold text-[#642754] hover:text-[#eb652c] transition-colors mb-3">
                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                        Back to My Profile
                    </a>
                    <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">Change Request</h1>
                    <p class="text-sm text-slate-600 mt-2 max-w-2xl leading-relaxed">
                        Request updates to your profile or portal content. Describe what is on record today and what you want changed.
                    </p>
                </div>
                <div class="shrink-0 rounded-2xl border border-[#642754]/15 bg-[#642754]/5 px-5 py-4 max-w-xs">
                    <p class="text-xs font-bold uppercase tracking-wider text-[#642754] mb-1">Need help?</p>
                    <p class="text-xs text-slate-600 leading-relaxed">HR reviews urgent requests first. Use clear labels so your request is approved faster.</p>
                </div>
            </div>

            <div class="<?= Html::encode($successVisibleClass) ?> mb-6 flex items-start gap-3 p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200">
                <span class="material-symbols-outlined text-emerald-600 shrink-0">check_circle</span>
                <p class="text-sm font-medium"><?= Html::encode($successMessage) ?></p>
            </div>
            <div class="<?= Html::encode($errorVisibleClass) ?> mb-6 flex items-start gap-3 p-4 rounded-xl bg-red-50 text-red-800 border border-red-200">
                <span class="material-symbols-outlined text-red-600 shrink-0">error</span>
                <p class="text-sm font-medium whitespace-pre-line"><?= Html::encode($errorMessage) ?></p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-[#642754] px-6 lg:px-10 py-6 lg:py-7">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h2 class="text-xl lg:text-2xl font-bold text-white flex items-center gap-2">
                                <span class="material-symbols-outlined">edit_document</span>
                                Submit a change request
                            </h2>
                            <p class="text-white/85 text-sm mt-2 max-w-xl">
                                Fields marked with <span class="text-[#eb652c] font-bold">*</span> are required. Your name and email are filled from your profile.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 text-white/90 text-sm">
                            <span class="material-symbols-outlined text-2xl">schedule</span>
                            <span>Typical HR review: 1–3 business days</span>
                        </div>
                    </div>
                </div>

                <div class="p-8 lg:p-12">
                    <?php $form = ActiveForm::begin([
                        'options' => ['class' => 'space-y-12'],
                        'fieldConfig' => [
                            'errorOptions' => ['class' => 'text-red-600 text-xs mt-2 font-medium'],
                        ],
                    ]); ?>

                    <section class="space-y-4">
                        <h3 class="<?= $sectionTitleClass ?>">Your details</h3>

                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-5 sm:p-6 flex flex-col gap-5">
                            <?php if ($model->hasAttribute('title')): ?>
                            <div class="<?= $fieldGroupClass ?> w-full">
                                <label class="<?= $labelClass ?>" for="request-title">
                                    Request title <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="<?= $iconClass ?>">title</span>
                                    <?= $form->field($model, 'title', ['options' => ['tag' => false]])
                                        ->textInput([
                                            'id' => 'request-title',
                                            'class' => $inputClass,
                                            'placeholder' => 'e.g. Update my department to Backend',
                                            'maxlength' => 255,
                                        ])
                                        ->label(false) ?>
                                </div>
                                <p class="<?= $hintClass ?>">Short headline for HR (e.g. department or job title update).</p>
                            </div>
                            <?php elseif (!EmployeeChangeRequest::hasColumn('title')): ?>
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                Request titles require: <code class="text-xs bg-white px-1 rounded">php yii migrate</code>
                            </div>
                            <?php endif; ?>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full <?= $model->hasAttribute('title') ? 'pt-5 border-t border-slate-200' : '' ?>">
                                <div class="<?= $fieldGroupClass ?>">
                                    <label class="<?= $labelClass ?>" for="name">Your name <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="<?= $iconClass ?>">person</span>
                                        <input class="<?= $inputReadonlyClass ?>" id="name" type="text" readonly
                                               value="<?= Html::encode($employee->full_name) ?>" />
                                    </div>
                                    <p class="<?= $hintClass ?>">From your profile.</p>
                                </div>
                                <div class="<?= $fieldGroupClass ?>">
                                    <label class="<?= $labelClass ?>" for="email">Your email</label>
                                    <div class="relative">
                                        <span class="<?= $iconClass ?>">mail</span>
                                        <input class="<?= $inputReadonlyClass ?>" id="email" type="email" readonly
                                               value="<?= Html::encode($employee->email) ?>" />
                                    </div>
                                    <p class="<?= $hintClass ?>">HR reply address.</p>
                                </div>
                                <div class="<?= $fieldGroupClass ?>">
                                    <label class="<?= $labelClass ?>" for="urgency">Priority</label>
                                    <?= $form->field($model, 'urgency', ['options' => ['tag' => false]])
                                        ->dropDownList($urgencyOptions, [
                                            'id' => 'urgency',
                                            'class' => $selectClass,
                                        ])
                                        ->label(false) ?>
                                    <p class="<?= $hintClass ?>">Urgent = time-sensitive only.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-8 pt-2">
                        <div class="border-t border-slate-200 pt-10">
                            <h3 class="<?= $sectionTitleClass ?>">Requested changes</h3>
                            <p class="text-sm text-slate-600 mt-3 max-w-2xl leading-relaxed">
                                Enter <strong class="text-slate-800">current</strong> and <strong class="text-slate-800">new</strong> content side by side on desktop for the clearest review.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-10">
                            <div class="flex flex-col rounded-2xl border border-slate-200/90 bg-slate-50/40 overflow-hidden shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                                <div class="px-6 py-5 border-b border-slate-200/90 bg-white">
                                    <label class="<?= $labelClass ?> flex items-center gap-2" for="current-text">
                                        <span class="material-symbols-outlined text-slate-500 text-lg">history_edu</span>
                                        Current content
                                    </label>
                                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">What is on your profile or portal today (optional but recommended).</p>
                                </div>
                                <div class="p-6 lg:p-7 flex-1 flex flex-col">
                                    <?= $form->field($model, 'old_value', ['options' => ['tag' => false]])
                                        ->textarea([
                                            'id' => 'current-text',
                                            'class' => $textareaClass,
                                            'placeholder' => "Example:\ncurrent department: New Jersey Crew\ncurrent job title: Software Engineer",
                                            'rows' => 10,
                                        ])
                                        ->label(false) ?>
                                </div>
                            </div>

                            <div class="flex flex-col rounded-2xl border border-[#eb652c]/35 bg-orange-50/40 overflow-hidden shadow-[0_1px_2px_rgba(235,101,44,0.08)] ring-1 ring-[#eb652c]/10">
                                <div class="px-6 py-5 border-b border-[#eb652c]/20 bg-white">
                                    <label class="<?= $labelClass ?> flex items-center gap-2 text-[#642754]" for="new-text">
                                        <span class="material-symbols-outlined text-[#eb652c] text-lg">edit_note</span>
                                        New content <span class="text-red-500">*</span>
                                    </label>
                                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">What you want HR to update it to.</p>
                                </div>
                                <div class="p-6 lg:p-7 flex-1 flex flex-col">
                                    <?= $form->field($model, 'new_value', ['options' => ['tag' => false]])
                                        ->textarea([
                                            'id' => 'new-text',
                                            'class' => $textareaClass . ' border-[#eb652c]/30 focus:ring-[#eb652c]/30 focus:border-[#eb652c]',
                                            'placeholder' => "Example:\nnew department: Backend\nnew job title: Senior Software Engineer",
                                            'rows' => 10,
                                        ])
                                        ->label(false) ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="xl:hidden rounded-xl border border-slate-200 bg-slate-50 p-5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Formatting tips</h3>
                        <ul class="text-xs text-slate-600 space-y-1.5 list-disc pl-4">
                            <li>One change per line with a label, e.g. <code class="bg-white px-1 rounded text-[11px]">field: value</code></li>
                            <li>Match labels in both boxes (current vs new) when possible.</li>
                        </ul>
                    </div>

                    <div class="pt-10 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                        <p class="text-xs text-slate-500 max-w-md">
                            By submitting, you confirm this request is accurate. HR may contact you for clarification.
                        </p>
                        <button type="submit"
                                class="inline-flex justify-center items-center gap-2 py-3 px-8 rounded-xl text-sm font-bold text-white bg-[#eb652c] hover:bg-[#d85a24] shadow-lg shadow-[#eb652c]/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#eb652c]/50 transition-all shrink-0">
                            <span class="material-symbols-outlined text-lg">send</span>
                            Send request to HR
                        </button>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->render('../layouts/_shell-end') ?>
