<?php

/**
 * @var yii\web\View $this
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'School Departments | CultureHR';

$cards = [
    [
        'title' => 'Infrastructure Hierarchy',
        'description' => 'Browse departments, grades, and sections across your school infrastructure.',
        'icon' => 'account_tree',
        'iconClass' => 'bg-[#642754]/10 text-[#642754]',
        'url' => Url::to(['/hr/school-department/hierarchy']),
        'button' => 'Open Infrastructure Hierarchy',
    ],
    [
        'title' => 'Add New Department',
        'description' => 'Create a new school department and configure its academic structure.',
        'icon' => 'add_business',
        'iconClass' => 'bg-[#eb652c]/10 text-[#eb652c]',
        'url' => Url::to(['/hr/school-department/create']),
        'button' => 'Add New Department',
    ],
];

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'school_departments']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'School Departments', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="w-full min-w-0 px-6 lg:px-10 xl:px-12 py-8">
                <div class="max-w-5xl mx-auto space-y-10">
                    <header>
                        <h1 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">School Departments Hub</h1>
                        <p class="text-slate-500 mt-2 text-lg">Select a section below to manage your school infrastructure.</p>
                    </header>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                        <?php foreach ($cards as $card): ?>
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center min-h-[320px]">
                                <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 <?= Html::encode($card['iconClass']) ?>">
                                    <span class="material-symbols-outlined text-4xl"><?= Html::encode($card['icon']) ?></span>
                                </div>
                                <h2 class="text-xl font-bold text-slate-900 mb-3"><?= Html::encode($card['title']) ?></h2>
                                <p class="text-slate-500 text-sm max-w-xs leading-relaxed flex-1"><?= Html::encode($card['description']) ?></p>
                                <a href="<?= Html::encode($card['url']) ?>"
                                   class="w-full mt-6 bg-[#eb652c] hover:bg-[#d85a24] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 no-underline">
                                    <?= Html::encode($card['button']) ?>
                                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
