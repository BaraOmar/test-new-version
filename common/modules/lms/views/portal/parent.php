<?php

/**
 * @var yii\web\View $this
 * @var common\models\User $user
 * @var common\modules\finance\models\FinanceAccountParentProfile|null $profile
 * @var string $userName
 * @var string $userRole
 * @var string $currentPage
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Parent Portal | LMS';

$successMessage = (string)Yii::$app->session->getFlash('success', '');
$children = $profile?->children ?? [];
$childrenCount = (int)($profile?->kids_count ?? count($children));
$phone = trim((string)($profile?->phone ?? ''));

?>
<?= $this->render('../layouts/_page-start') ?>
    <header>
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Welcome, <?= Html::encode($userName) ?></h2>
        <p class="text-slate-500 mt-2 text-lg">Your parent learning portal.</p>
    </header>

    <?php if ($successMessage !== ''): ?>
        <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-medium">
            <?= Html::encode($successMessage) ?>
        </div>
    <?php endif; ?>

    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Overview</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm min-h-[140px] flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Login Email</p>
                    <div class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center bg-[#642754]/10 text-[#642754]">
                        <span class="material-symbols-outlined">mail</span>
                    </div>
                </div>
                <p class="text-sm sm:text-base font-bold text-slate-900 mt-3 break-all leading-relaxed"
                   title="<?= Html::encode($user->email) ?>">
                    <?= Html::encode($user->email) ?>
                </p>
            </div>
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm min-h-[140px] flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Registered Children</p>
                    <div class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center bg-[#eb652c]/10 text-[#eb652c]">
                        <span class="material-symbols-outlined">family_restroom</span>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900 mt-3"><?= $childrenCount ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm min-h-[140px] flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Phone</p>
                    <div class="w-12 h-12 shrink-0 rounded-xl flex items-center justify-center bg-emerald-50 text-emerald-600">
                        <span class="material-symbols-outlined">call</span>
                    </div>
                </div>
                <p class="text-2xl font-bold text-slate-900 mt-3"><?= Html::encode($phone !== '' ? $phone : '—') ?></p>
            </div>
        </div>
    </section>

    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Modules</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center h-full min-h-[360px]">
                <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 bg-blue-50 text-blue-700">
                    <span class="material-symbols-outlined text-4xl">person</span>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">My Profile</h3>
                <p class="text-slate-500 mb-8 max-w-sm leading-relaxed flex-1">View your personal, contact, and account information.</p>
                <a href="<?= Html::encode(Url::to(['profile'])) ?>"
                   class="w-full bg-[#642754] hover:bg-[#4a2a51] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-auto no-underline">
                    View My Profile
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-shadow flex flex-col items-center text-center h-full min-h-[360px]">
                <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shrink-0 bg-indigo-50 text-indigo-700">
                    <span class="material-symbols-outlined text-4xl">lock_reset</span>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Change Password</h3>
                <p class="text-slate-500 mb-8 max-w-sm leading-relaxed flex-1">Update the password you use to sign in to the parent portal.</p>
                <a href="<?= Html::encode(Url::to(['change-password'])) ?>"
                   class="w-full bg-[#642754] hover:bg-[#4a2a51] text-white font-bold py-3.5 px-6 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-auto no-underline">
                    Change Password
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>
    </section>

    <section>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Registered Children</h3>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                <h4 class="text-sm font-black text-slate-700 uppercase tracking-wide m-0">Children</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-slate-500 text-xs uppercase tracking-wide">
                            <th class="px-6 py-3 text-left font-bold">#</th>
                            <th class="px-6 py-3 text-left font-bold">Full Name</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($children === []): ?>
                            <tr>
                                <td colspan="2" class="px-6 py-10 text-center text-slate-500">No children registered.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($children as $child): ?>
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-6 py-4 font-bold text-slate-900"><?= (int)$child->child_index ?></td>
                                <td class="px-6 py-4 text-slate-800 font-semibold"><?= Html::encode($child->getFullName()) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?= $this->render('../layouts/_page-end') ?>
