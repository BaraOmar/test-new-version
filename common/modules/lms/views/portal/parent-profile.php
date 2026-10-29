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

$this->title = 'My Profile | LMS';

$dash = static function (?string $value): string {
    $value = trim((string)$value);

    return $value !== '' ? $value : '—';
};

$children = $profile?->children ?? [];

?>
<?= $this->render('../layouts/_page-start') ?>
    <header>
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">My Profile</h2>
        <p class="text-slate-500 mt-2 text-lg">Your personal and contact information.</p>
    </header>

    <?php if ($profile === null): ?>
        <div class="p-5 rounded-xl bg-amber-50 text-amber-900 border border-amber-200 text-sm font-medium">
            Your parent profile could not be found. Please contact the school administration.
        </div>
    <?php else: ?>
        <section>
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Personal Information</h3>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex flex-wrap items-center gap-4">
                    <span class="inline-flex items-center justify-center size-12 rounded-xl bg-[#642754]/10 text-[#642754] shrink-0">
                        <span class="material-symbols-outlined text-2xl">person</span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-wide m-0">Full Name</p>
                        <p class="text-xl font-black text-slate-900 mt-1 leading-snug"><?= Html::encode($profile->getFullName()) ?></p>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="inline-flex items-center justify-center size-9 rounded-lg bg-[#eb652c]/10 text-[#eb652c]">
                            <span class="material-symbols-outlined text-[18px]">call</span>
                        </span>
                        <h4 class="text-sm font-black text-slate-700 uppercase tracking-wide m-0">Contact Details</h4>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-x-6 gap-y-5 text-sm">
                        <div>
                            <dt class="text-slate-400 font-bold text-xs uppercase tracking-wide">Phone</dt>
                            <dd class="text-slate-900 font-semibold mt-1"><?= Html::encode($dash($profile->phone)) ?></dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-bold text-xs uppercase tracking-wide">Emergency Phone</dt>
                            <dd class="text-slate-900 font-semibold mt-1"><?= Html::encode($dash($profile->emergency_phone_2)) ?></dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-bold text-xs uppercase tracking-wide">Contact Email</dt>
                            <dd class="text-slate-900 font-semibold mt-1 break-all"><?= Html::encode($dash($profile->email)) ?></dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-bold text-xs uppercase tracking-wide">Children Count</dt>
                            <dd class="text-slate-900 font-semibold mt-1"><?= (int)$profile->kids_count ?></dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <section>
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Portal Account</h3>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <span class="inline-flex items-center justify-center size-10 rounded-xl bg-blue-50 text-blue-700">
                        <span class="material-symbols-outlined">lock</span>
                    </span>
                    <h4 class="text-sm font-black text-slate-700 uppercase tracking-wide m-0">Login Credentials</h4>
                </div>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-slate-400 font-bold text-xs uppercase tracking-wide">Login Email</dt>
                        <dd class="text-slate-900 font-semibold mt-1 break-all"><?= Html::encode($dash($user->email)) ?></dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-bold text-xs uppercase tracking-wide">Account Role</dt>
                        <dd class="text-slate-900 font-semibold mt-1">Parent</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section>
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Registered Children</h3>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
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
    <?php endif; ?>
<?= $this->render('../layouts/_page-end') ?>
