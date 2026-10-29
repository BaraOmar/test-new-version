<?php

/**
 * No Employee Profile.
 *
 * @var yii\web\View $this
 * @var string|null $pageTitle
 */

use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

$pageTitle = $pageTitle ?? 'Dashboard';
$this->title = $pageTitle;

$user = Yii::$app->user->identity;
$userName = $user instanceof User ? trim((string)($user->username ?? $user->email ?? 'User')) : 'User';

?>

<?= $this->render('@frontend/modules/employee/views/layouts/_shell-start', [
    'pageTitle' => $pageTitle,
    'currentPage' => 'dashboard',
    'userName' => $userName,
    'userRole' => 'Account',
]) ?>
    <div class="max-w-[900px] mx-auto px-6 lg:px-10 py-12">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
            <div class="flex items-start gap-4">
                <div class="size-12 rounded-xl bg-[#4a2a51]/10 text-[#4a2a51] flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">badge</span>
                </div>
                <div class="min-w-0">
                    <h2 class="text-slate-900 text-2xl font-black tracking-tight">No Employee Profile</h2>
                    <p class="text-slate-600 mt-1">
                        Your account is not linked to an employee record yet.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="<?= Url::to(['/site/logout']) ?>"
                   onclick="event.preventDefault(); this.closest('body').querySelector('form[data-logout-form]').submit();"
                   class="inline-flex items-center justify-center gap-2 rounded-lg h-10 px-4 bg-[#f27a44] hover:bg-[#eb652c] text-white text-sm font-bold shadow-sm transition-all">
                    <span class="material-symbols-outlined text-lg">logout</span>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
<?= $this->render('@frontend/modules/employee/views/layouts/_shell-end') ?>

<?= Html::beginForm(['/site/logout'], 'post', ['class' => 'hidden', 'data-logout-form' => '1']) ?>
<?= Html::endForm() ?>
