<?php

/**
 * @var yii\web\View $this
 * @var string $activeTab record|records
 */

use yii\helpers\Html;
use yii\helpers\Url;

$activeTab = $activeTab ?? 'record';

?>
<div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1">
    <a href="<?= Html::encode(Url::to(['clinic-visits'])) ?>"
       class="inline-flex items-center h-9 px-4 rounded-md text-sm font-semibold no-underline <?= $activeTab === 'record' ? 'bg-white text-[#1e3a8a] shadow-sm' : 'text-slate-600' ?>">
        Record New Visit
    </a>
    <a href="<?= Html::encode(Url::to(['clinic-visit-records'])) ?>"
       class="inline-flex items-center h-9 px-4 rounded-md text-sm font-semibold no-underline <?= $activeTab === 'records' ? 'bg-white text-[#1e3a8a] shadow-sm' : 'text-slate-600' ?>">
        Visit Records
    </a>
</div>
