<?php

/**
 * UI-only placeholder (HR side) to be ported pixel-perfect next.
 *
 * @var yii\web\View $this
 * @var string $userName
 */

use yii\helpers\Html;

$this->title = 'Survey Management | CultureHR';

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => 'surveys']) ?>
    <main class="flex-1 flex flex-col overflow-y-auto">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Surveys', 'userName' => $userName]) ?>
        <div class="p-8 max-w-7xl mx-auto w-full bg-[#f8f4f8]">
            <div class="bg-white border border-slate-200 rounded-2xl p-10 shadow-sm">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Survey Management</h2>
                <p class="text-slate-500 mt-2">UI will be cloned pixel-perfect from the legacy HR survey list.</p>
            </div>
        </div>
    </main>
</div>

