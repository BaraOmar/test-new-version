<?php

/**
 * UI-only placeholder (HR side).
 *
 * @var yii\web\View $this
 * @var string $userName
 */

use yii\helpers\Html;

$this->title = 'LMS | CultureHR';

?>

<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => 'lms']) ?>
    <main class="flex-1 flex flex-col overflow-y-auto">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'LMS', 'userName' => $userName]) ?>
        <div class="p-8 max-w-7xl mx-auto w-full bg-[#f8f4f8]">
            <div class="bg-white border border-slate-200 rounded-2xl p-10 shadow-sm">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">LMS</h2>
                <p class="text-slate-500 mt-2">UI will be cloned pixel-perfect from the legacy LMS admin pages.</p>
            </div>
        </div>
    </main>
</div>

