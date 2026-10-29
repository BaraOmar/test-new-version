<?php

/**
 * HR portal page header (shared white design).
 *
 * @var yii\web\View $this
 * @var string $pageTitle
 * @var string|null $pageSubtitle
 * @var string|null $headerActionsHtml
 */

echo $this->render('@frontend/views/layouts/_portal-topbar', [
    'pageTitle' => $pageTitle ?? 'Dashboard',
    'pageSubtitle' => $pageSubtitle ?? null,
    'headerActionsHtml' => $headerActionsHtml ?? null,
]);
