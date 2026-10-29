<?php

/**
 * @var yii\web\View $this
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\employee\models\SchoolStudentClinicVisit;
use common\modules\employee\models\SchoolStudentHealthFile;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Student Health | CultureHR';

$cards = [];
if (SchoolStudentHealthFile::isEnabled()) {
    $cards[] = [
        'title' => 'Health Files',
        'description' => 'Browse, create, and update student medical profiles, allergies, and chronic conditions.',
        'icon' => 'folder_shared',
        'iconClass' => 'bg-rose-50 text-rose-700',
        'url' => ['files'],
        'button' => 'Open Health Files',
    ];
}
if (SchoolStudentClinicVisit::isEnabled()) {
    $cards[] = [
        'title' => 'Record Visit',
        'description' => 'File a new clinic visit with symptoms, medicine administration, and care outcomes.',
        'icon' => 'medical_services',
        'iconClass' => 'bg-sky-50 text-sky-700',
        'url' => ['clinic-visits'],
        'button' => 'Record Clinic Visit',
    ];
    $cards[] = [
        'title' => 'Visit Records',
        'description' => 'Review filed clinic visits, filter by student or date, and follow up on outcomes.',
        'icon' => 'clinical_notes',
        'iconClass' => 'bg-violet-50 text-violet-700',
        'url' => ['clinic-visit-records'],
        'button' => 'View Visit Records',
    ];
}

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage]) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Student Health', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-5xl mx-auto px-6 lg:px-8 py-8">
                <header class="text-center lg:text-left mb-10">
                    <h1 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">Student Health Hub</h1>
                    <p class="text-slate-500 mt-2 text-lg">Select a section below to manage student medical records and clinic visits.</p>
                </header>

                <?php if ($cards === []): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center text-slate-500">
                        Student health tables are not available yet. Run <code class="text-sm bg-slate-100 px-2 py-1 rounded">php yii migrate/up</code> to enable this module.
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                        <?php foreach ($cards as $card): ?>
                            <div class="group bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg hover:border-[#1e3a8a]/20 transition-all flex flex-col items-center text-center min-h-[320px]">
                                <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 transition-transform group-hover:scale-105 <?= Html::encode($card['iconClass']) ?>">
                                    <span class="material-symbols-outlined text-5xl"><?= Html::encode($card['icon']) ?></span>
                                </div>
                                <div class="space-y-2 flex-1">
                                    <h2 class="text-xl font-bold text-slate-900"><?= Html::encode($card['title']) ?></h2>
                                    <p class="text-slate-500 text-sm max-w-xs mx-auto leading-relaxed"><?= Html::encode($card['description']) ?></p>
                                </div>
                                <a href="<?= Html::encode(Url::to($card['url'])) ?>"
                                   class="w-full mt-6 font-bold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2 no-underline bg-[#1e3a8a] hover:bg-[#1e40af] text-white shadow-md">
                                    <?= Html::encode($card['button']) ?>
                                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
