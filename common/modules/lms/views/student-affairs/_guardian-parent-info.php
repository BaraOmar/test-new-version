<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent $model
 * @var callable $display
 * @var string $accentTextClass
 * @var string $accentBgClass
 */

use yii\helpers\Html;

$contactTile = static function (
    string $icon,
    string $label,
    ?string $value,
    string $accentTextClass,
    string $accentBgClass,
    bool $emphasize = false
) use ($display): string {
    $valueText = Html::encode($display($value));

    return '<div class="flex gap-4 p-4 rounded-xl bg-white border border-slate-100 shadow-sm">'
        . '<div class="flex-shrink-0 w-10 h-10 rounded-lg ' . $accentBgClass . ' flex items-center justify-center">'
        . '<span class="material-symbols-outlined text-[20px] ' . $accentTextClass . '">' . Html::encode($icon) . '</span>'
        . '</div>'
        . '<div class="min-w-0 flex-1">'
        . '<p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">' . Html::encode($label) . '</p>'
        . '<p class="text-sm font-semibold text-slate-900 mt-1 break-all' . ($emphasize ? ' text-red-600' : '') . '">' . $valueText . '</p>'
        . '</div>'
        . '</div>';
};

?>
<div class="p-6 lg:p-8">
    <article class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-slate-50 p-6 lg:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between pb-6 border-b border-slate-200/80">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Guardian Name</p>
                <p class="text-xl lg:text-2xl font-bold text-slate-900 mt-1 break-words"><?= Html::encode($display($model->guardian_name)) ?></p>
            </div>
            <span class="inline-flex self-start px-3 py-1.5 rounded-lg <?= $accentBgClass ?> <?= $accentTextClass ?> text-[10px] font-black uppercase tracking-wide">
                Main Contact
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
            <?= $contactTile('call', 'Parent Phone Number', $model->phone, $accentTextClass, $accentBgClass) ?>
            <?= $contactTile('contact_emergency', 'Emergency Phone', $model->emergency_contact, $accentTextClass, $accentBgClass, true) ?>
            <?= $contactTile('mail', 'Contact Email', $model->getGuardianContactEmail(), $accentTextClass, $accentBgClass) ?>
        </div>
    </article>
</div>
