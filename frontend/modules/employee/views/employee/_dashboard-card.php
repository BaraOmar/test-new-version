<?php

/**
 * @var string $icon Material icon name
 * @var string $tone slate|amber|blue|orange|teal
 * @var string $title
 * @var string $description
 * @var string $metricsHtml HTML for metrics block
 * @var string $ctaUrl
 * @var string $ctaLabel
 * @var string $cardClass Extra classes on card root
 * @var string $badgeHtml Optional badge HTML in header
 */

use yii\helpers\Html;

$toneClasses = [
    'slate' => 'employee-dash-card__icon--slate',
    'amber' => 'employee-dash-card__icon--amber',
    'blue' => 'employee-dash-card__icon--blue',
    'orange' => 'employee-dash-card__icon--orange',
    'teal' => 'employee-dash-card__icon--teal',
];
$iconToneClass = $toneClasses[$tone] ?? $toneClasses['slate'];
$cardClass = trim('employee-dash-card ' . ($cardClass ?? ''));

?>
<article class="<?= Html::encode($cardClass) ?>">
    <div class="employee-dash-card__header">
        <div class="employee-dash-card__icon <?= Html::encode($iconToneClass) ?>">
            <span class="material-symbols-outlined" aria-hidden="true"><?= Html::encode($icon) ?></span>
        </div>
        <?= $badgeHtml ?? '' ?>
    </div>
    <h3 class="employee-dash-card__title"><?= Html::encode($title) ?></h3>
    <p class="employee-dash-card__desc"><?= Html::encode($description) ?></p>
    <div class="employee-dash-card__metrics">
        <?= $metricsHtml ?>
    </div>
    <a href="<?= Html::encode($ctaUrl) ?>" class="employee-dash-card__cta">
        <?= Html::encode($ctaLabel) ?>
    </a>
</article>
