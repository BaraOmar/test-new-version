<?php

/**
 * @var yii\web\View $this
 */

use yii\helpers\Html;

?>
<div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-900 mb-8" role="alert">
    <p class="text-sm font-bold uppercase tracking-wide text-red-700">Account terminated</p>
    <p class="mt-1 text-sm leading-relaxed">
        Your employment has ended. You can review your profile only.
        <?= Html::encode('Surveys, evaluations, training, and change requests are no longer available.') ?>
    </p>
</div>
