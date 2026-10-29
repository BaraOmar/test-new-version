<?php

/**
 * @var common\modules\employee\models\EmployeeChangeRequest $request
 */

use common\helpers\ChangeRequestContentFormatter;
use yii\helpers\Html;

$oldParsed = ChangeRequestContentFormatter::parse((string)($request->old_value ?? ''));
$newParsed = ChangeRequestContentFormatter::parse((string)($request->new_value ?? ''));
$rows = ChangeRequestContentFormatter::buildComparisonRows($oldParsed['fields'], $newParsed['fields']);

if ($rows === []) {
    return;
}

?>

<section class="mb-8 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">compare_arrows</span>
            Field comparison
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Highlighted rows show values that differ between current and proposed content.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[520px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700">
                    <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 w-[28%]">Field</th>
                    <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 w-[36%]">Current</th>
                    <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-primary w-[36%]">Proposed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80">
                <?php foreach ($rows as $row): ?>
                    <tr class="<?= $row['changed'] ? 'bg-amber-50/60 dark:bg-amber-900/10' : '' ?>">
                        <td class="px-6 py-4 align-top font-semibold text-slate-800 dark:text-slate-200">
                            <?= Html::encode($row['label']) ?>
                            <?php if ($row['changed']): ?>
                                <span class="ml-2 inline-flex text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Changed</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 align-top text-slate-600 dark:text-slate-400 break-words">
                            <?= Html::encode($row['current']) ?>
                        </td>
                        <td class="px-6 py-4 align-top font-medium text-slate-800 dark:text-slate-100 break-words">
                            <?php if ($row['changed']): ?>
                                <span class="text-emerald-700 dark:text-emerald-400"><?= Html::encode($row['proposed']) ?></span>
                            <?php else: ?>
                                <?= Html::encode($row['proposed']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
