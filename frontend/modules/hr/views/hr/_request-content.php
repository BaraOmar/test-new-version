<?php

/**
 * @var string $content
 * @var string $variant current|proposed
 */

use common\helpers\ChangeRequestContentFormatter;
use yii\helpers\Html;

$parsed = ChangeRequestContentFormatter::parse($content);
$variant = $variant ?? 'current';
$isProposed = $variant === 'proposed';
$hasStructure = ChangeRequestContentFormatter::hasStructuredContent($parsed);

$intentClass = $isProposed
    ? 'border-primary/25 bg-primary/5 dark:bg-primary/10'
    : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50';
$fieldLabelClass = 'text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400';
$valueClass = 'text-sm font-medium text-slate-800 dark:text-slate-200 leading-relaxed break-words';

?>

<?php if ($parsed['plain'] === ''): ?>
    <p class="text-sm text-slate-400 dark:text-slate-500 italic">No content provided.</p>
<?php elseif ($hasStructure): ?>
    <div class="space-y-3">
        <?php foreach ($parsed['blocks'] as $block): ?>
            <?php
            $type = (string)($block['type'] ?? 'text');
            if ($type === 'field' || $type === 'intent'):
                ?>
                <div class="rounded-xl border px-4 py-3 <?= $intentClass ?>">
                    <p class="<?= $fieldLabelClass ?> mb-1.5">
                        <?= Html::encode((string)($block['label'] ?? 'Detail')) ?>
                    </p>
                    <p class="<?= $valueClass ?>">
                        <?= Html::encode((string)($block['value'] ?? '')) ?>
                    </p>
                </div>
            <?php elseif ($type === 'note'): ?>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed pl-3 border-l-2 border-slate-200 dark:border-slate-600">
                    <?= Html::encode((string)($block['text'] ?? '')) ?>
                </p>
            <?php else: ?>
                <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                    <?= Html::encode((string)($block['text'] ?? '')) ?>
                </p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-4 py-4">
        <?php
        $paragraphs = preg_split('/\n\s*\n/', $parsed['plain']) ?: [$parsed['plain']];
        foreach ($paragraphs as $paragraph):
            $paragraph = trim((string)$paragraph);
            if ($paragraph === '') {
                continue;
            }
            ?>
            <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line break-words mb-3 last:mb-0">
                <?= Html::encode($paragraph) ?>
            </p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
