<?php

use yii\helpers\Html;

/** @var array<string, mixed> $context */
/** @var string $body */

$studentName = Html::encode((string)($context['student_name'] ?? ''));
$actionLabel = Html::encode((string)($context['action_label'] ?? ''));
$visitDate = Html::encode((string)($context['visit_date'] ?? ''));

?>
<div style="font-family: Arial, sans-serif; color: #1e293b; line-height: 1.6; max-width: 640px;">
    <h2 style="margin: 0 0 16px; color: #1e3a8a;">Clinic Visit Update</h2>
    <p style="margin: 0 0 12px;">Student: <strong><?= $studentName ?></strong></p>
    <p style="margin: 0 0 12px;">Visit date: <strong><?= $visitDate ?></strong></p>
    <p style="margin: 0 0 16px;">Action taken: <strong><?= $actionLabel ?></strong></p>
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; white-space: pre-wrap;"><?= Html::encode($body) ?></div>
</div>
