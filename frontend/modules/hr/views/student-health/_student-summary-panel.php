<?php

/**
 * @var array<string, mixed> $summary
 */

use yii\helpers\Html;

$summary = $summary ?? [];

?>
<div class="rounded-lg border border-slate-200 bg-slate-50 p-4 border-l-4 <?= Html::encode($summary['alert_border'] ?? 'border-l-sky-500') ?>">
    <p class="font-bold text-slate-900"><?= Html::encode($summary['name'] ?? '') ?></p>
    <p class="text-xs text-slate-500 mt-1">
        ID: <?= Html::encode($summary['student_code'] ?? '') ?>
        · <?= Html::encode($summary['class_label'] ?? '') ?>
    </p>
    <div class="mt-3 space-y-2">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Allergies</p>
            <span class="inline-flex px-2 py-1 rounded text-[10px] font-bold tracking-wide uppercase <?= Html::encode($summary['allergy_class'] ?? 'bg-slate-100 text-slate-600') ?>">
                <?= Html::encode($summary['allergy_label'] ?? 'No Known Allergies') ?>
            </span>
        </div>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Chronic</p>
            <div class="flex flex-wrap gap-2">
                <?php if (!empty($summary['chronic_tags'])): ?>
                    <?php foreach ($summary['chronic_tags'] as $tag): ?>
                        <span class="inline-flex px-2 py-1 rounded text-[10px] font-bold tracking-wide uppercase bg-sky-100 text-sky-700">Chronic: <?= Html::encode($tag) ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Emergency Contact</p>
            <p class="text-sm text-slate-700"><?= Html::encode($summary['emergency_contact'] ?? '—') ?></p>
        </div>
    </div>
</div>
