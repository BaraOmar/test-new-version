<?php

/**
 * @var yii\web\View $this
 * @var common\modules\employee\models\SchoolStudent $model
 * @var string $activeTab profile|health|attendance|section
 */

use common\components\LmsPortal;

$user = Yii::$app->user->identity;
$toggleUrls = $user instanceof \common\models\User
    ? LmsPortal::studentProfileSectionToggleUrls($user, $model)
    : null;

if ($toggleUrls === null) {
    return;
}

echo $this->render('_profile-section-toggle', [
    'activeTab' => $activeTab,
    'profileUrl' => $toggleUrls['profile'],
    'healthUrl' => $toggleUrls['health'] ?? null,
    'attendanceUrl' => $toggleUrls['attendance'] ?? null,
    'marksUrl' => $toggleUrls['marks'] ?? null,
    'sectionUrl' => $toggleUrls['section'],
]);
