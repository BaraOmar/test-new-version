<?php

/**
 * @var yii\web\View $this
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Transportations | CultureHR';

$cards = [
    [
        'title' => 'Areas',
        'description' => 'Define pickup zones and geographic areas for school transportation routes.',
        'icon' => 'map',
        'iconClass' => 'bg-blue-50 text-blue-700',
        'url' => Url::to(['/hr/transportation/areas']),
        'button' => 'Manage Areas',
    ],
    [
        'title' => 'Create Buses',
        'description' => 'Register buses with plate numbers, capacity, and operational status.',
        'icon' => 'directions_bus',
        'iconClass' => 'bg-emerald-50 text-emerald-600',
        'url' => Url::to(['/hr/transportation/buses']),
        'button' => 'Manage Buses',
    ],
    [
        'title' => 'Assign Buses',
        'description' => 'Assign buses to areas, drivers, supervisors, and enrolled students.',
        'icon' => 'assignment_ind',
        'iconClass' => 'bg-[#642754]/10 text-[#642754]',
        'url' => Url::to(['/hr/transportation/assign-buses']),
        'button' => 'Assign Buses',
    ],
];

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'transportation']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Transportations', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="w-full min-w-0 px-6 lg:px-10 xl:px-12 py-8">
                <div class="max-w-5xl mx-auto space-y-10">
                    <header>
                        <h1 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">Transportations</h1>
                        <p class="text-slate-500 mt-2 text-lg">Manage areas, buses, drivers, supervisors, and student assignments.</p>
                    </header>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                        <?php foreach ($cards as $card): ?>
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center min-h-[300px]">
                                <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 <?= Html::encode($card['iconClass']) ?>">
                                    <span class="material-symbols-outlined text-4xl"><?= Html::encode($card['icon']) ?></span>
                                </div>
                                <h2 class="text-xl font-black text-slate-900 mb-2"><?= Html::encode($card['title']) ?></h2>
                                <p class="text-sm text-slate-500 mb-6 flex-1"><?= Html::encode($card['description']) ?></p>
                                <a href="<?= Html::encode($card['url']) ?>"
                                   class="inline-flex items-center gap-2 h-11 px-6 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold no-underline">
                                    <?= Html::encode($card['button']) ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
