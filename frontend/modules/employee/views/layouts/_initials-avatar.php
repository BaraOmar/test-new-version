<?php

/**
 * Generic initials avatar (no profile photos).
 *
 * @var yii\web\View $this
 * @var string|null $name
 * @var string $sizeClass e.g. size-10, size-32
 * @var string $rounded e.g. rounded-full, rounded-2xl
 * @var string $textClass e.g. text-sm, text-2xl
 */

use yii\helpers\Html;

$name = trim((string)($name ?? ''));
$sizeClass = $sizeClass ?? 'size-10';
$rounded = $rounded ?? 'rounded-full';
$textClass = $textClass ?? 'text-sm';

$initials = '—';
if ($name !== '') {
    $parts = preg_split('/\s+/u', $name, 2, PREG_SPLIT_NO_EMPTY);
    $first = mb_substr($parts[0], 0, 1);
    $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : mb_substr($parts[0], 1, 1);
    $initials = mb_strtoupper($first . $second);
}

?>
<div class="<?= Html::encode($sizeClass) ?> <?= Html::encode($rounded) ?> shrink-0 bg-[#eb652c]/15 text-[#eb652c] border border-[#eb652c]/25 flex items-center justify-center font-bold <?= Html::encode($textClass) ?>">
    <?= Html::encode($initials) ?>
</div>
