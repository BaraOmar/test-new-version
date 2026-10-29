<?php

/**
 * @var yii\web\View $this
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Subjects | CultureHR';

$cards = [
    [
        'title' => 'Create Subject',
        'description' => 'Add a subject to a department and assign credits for one or more grades.',
        'icon' => 'add_circle',
        'iconClass' => 'bg-emerald-50 text-emerald-600',
        'url' => Url::to(['/hr/school-department/create-subject']),
        'button' => 'Create Subject',
    ],
    [
        'title' => 'Subject List',
        'description' => 'Browse, filter, view, update, and manage subjects with their grade credits.',
        'icon' => 'menu_book',
        'iconClass' => 'bg-blue-50 text-blue-700',
        'url' => Url::to(['/hr/school-department/subjects']),
        'button' => 'Open Subject List',
    ],
];

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'subjects']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Subjects', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="w-full min-w-0 px-6 lg:px-10 xl:px-12 py-8">
                <div class="max-w-5xl mx-auto space-y-10">
                    <header>
                        <nav class="text-sm text-slate-500 mb-4">
                            <span>Academic</span>
                            <span class="mx-2">/</span>
                            <span class="text-slate-900 font-semibold">Subjects</span>
                        </nav>
                        <h1 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">Subject Management</h1>
                        <p class="text-slate-500 mt-2 text-lg">Create subjects, assign grade credits, and manage your academic catalog.</p>
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
                                   class="w-full mt-6 bg-[#1e3a8a] hover:bg-[#1e40af] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 no-underline">
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
