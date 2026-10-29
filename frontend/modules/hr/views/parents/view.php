<?php

/**
 * @var yii\web\View $this
 * @var common\modules\finance\models\FinanceAccountParentProfile $profile
 * @var string $userName
 * @var string $currentPage
 */

use common\modules\finance\models\FinanceAccountParentChild;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Parent Profile | CultureHR';

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'parents_directory']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Parents Directory', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-8">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                        <div>
                            <a href="<?= Html::encode(Url::to(['index'])) ?>"
                               class="group inline-flex items-center gap-3 no-underline mb-4 text-slate-500 hover:text-[#1e3a8a] transition-colors">
                                <span class="inline-flex items-center justify-center size-11 rounded-xl border border-slate-200 bg-slate-50 text-[#1e3a8a] shadow-sm group-hover:bg-[#1e3a8a] group-hover:text-white group-hover:border-[#1e3a8a] transition-colors">
                                    <span class="material-symbols-outlined text-[28px] leading-none">arrow_back</span>
                                </span>
                                <span class="text-sm font-bold uppercase tracking-wide">Back to Parents Directory</span>
                            </a>
                            <h1 class="text-2xl font-black text-[#1e3a8a]"><?= Html::encode($profile->getFullName()) ?></h1>
                            <p class="text-slate-500 mt-1">Parent profile and registered children</p>
                        </div>
                        <a href="<?= Html::encode(Url::to(['update', 'id' => (int)$profile->id])) ?>"
                           class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold no-underline">
                            <span class="material-symbols-outlined text-lg">edit</span>
                            Edit Parent
                        </a>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
                        <div class="p-5 rounded-xl border border-slate-200 bg-slate-50/60">
                            <h3 class="text-sm font-black text-slate-700 uppercase tracking-wide mb-4">Parent Information</h3>
                            <dl class="space-y-3 text-sm">
                                <div><dt class="text-slate-400 font-bold text-xs uppercase">First Name</dt><dd class="text-slate-900 font-semibold mt-1"><?= Html::encode($profile->first_name) ?></dd></div>
                                <div><dt class="text-slate-400 font-bold text-xs uppercase">Second Name</dt><dd class="text-slate-900 font-semibold mt-1"><?= Html::encode($profile->second_name ?: '—') ?></dd></div>
                                <div><dt class="text-slate-400 font-bold text-xs uppercase">Third Name</dt><dd class="text-slate-900 font-semibold mt-1"><?= Html::encode($profile->third_name ?: '—') ?></dd></div>
                                <div><dt class="text-slate-400 font-bold text-xs uppercase">Family Name</dt><dd class="text-slate-900 font-semibold mt-1"><?= Html::encode($profile->family_name) ?></dd></div>
                                <div><dt class="text-slate-400 font-bold text-xs uppercase">Children Count</dt><dd class="text-slate-900 font-semibold mt-1"><?= (int)$profile->kids_count ?></dd></div>
                            </dl>
                        </div>

                        <div class="p-5 rounded-xl border border-slate-200 bg-slate-50/60">
                            <h3 class="text-sm font-black text-slate-700 uppercase tracking-wide mb-4">Contact Information</h3>
                            <dl class="space-y-3 text-sm">
                                <div><dt class="text-slate-400 font-bold text-xs uppercase">Phone</dt><dd class="text-slate-900 font-semibold mt-1"><?= Html::encode($profile->phone) ?></dd></div>
                                <div><dt class="text-slate-400 font-bold text-xs uppercase">Emergency Phone</dt><dd class="text-slate-900 font-semibold mt-1"><?= Html::encode($profile->emergency_phone_2) ?></dd></div>
                                <div><dt class="text-slate-400 font-bold text-xs uppercase">Email</dt><dd class="text-slate-900 font-semibold mt-1"><?= Html::encode($profile->email ?: '—') ?></dd></div>
                            </dl>
                        </div>
                    </div>

                    <div class="mb-5 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
                            <span class="inline-flex items-center justify-center size-8 rounded-lg bg-blue-50 text-[#1e3a8a]">
                                <span class="material-symbols-outlined text-[18px]">lock</span>
                            </span>
                            <h3 class="text-sm font-extrabold text-[#1e3a8a] m-0">Portal Credentials</h3>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <p class="text-slate-400 font-bold text-xs uppercase tracking-wide mb-2">Login Email</p>
                                    <p class="text-slate-900 font-semibold text-sm break-all"><?= Html::encode((string)($profile->user?->email ?? '—')) ?></p>
                                </div>
                                <div>
                                    <p class="text-slate-400 font-bold text-xs uppercase tracking-wide mb-2">Login Password</p>
                                    <?php $storedPassword = trim((string)($profile->login_password_plain ?? '')); ?>
                                    <?php if ($storedPassword !== ''): ?>
                                        <div class="flex items-center gap-2 w-full">
                                            <input type="password" id="parent-view-password" readonly value="<?= Html::encode($storedPassword) ?>"
                                                   class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm font-mono">
                                            <button type="button" data-password-toggle="parent-view-password"
                                                    class="shrink-0 inline-flex items-center justify-center p-1 text-slate-500 hover:text-[#1e3a8a] bg-transparent border-0 cursor-pointer"
                                                    aria-label="Show password">
                                                <span class="material-symbols-outlined text-2xl leading-none">visibility</span>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-slate-900 font-semibold text-sm">—</p>
                                        <p class="text-xs text-slate-500 mt-1">Changed by the parent or not available. Use Edit Parent to set a new password.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-sm font-black text-slate-700 uppercase tracking-wide">Registered Children</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-white text-slate-500 text-xs uppercase tracking-wide">
                                        <th class="px-4 py-3 text-left">#</th>
                                        <th class="px-4 py-3 text-left">Full Name</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if ($profile->children === []): ?>
                                        <tr>
                                            <td colspan="2" class="px-4 py-8 text-center text-slate-500">No children registered.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($profile->children as $child): ?>
                                        <?php if (!$child instanceof FinanceAccountParentChild) {
                                            continue;
                                        } ?>
                                        <tr>
                                            <td class="px-4 py-3 font-bold text-slate-900"><?= (int)$child->child_index ?></td>
                                            <td class="px-4 py-3 text-slate-800 font-semibold"><?= Html::encode($child->getFullName()) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php
$this->registerJs(<<<'JS'
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.getAttribute('data-password-toggle'));
        if (!input) return;
        const icon = button.querySelector('.material-symbols-outlined');
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        if (icon) icon.textContent = showing ? 'visibility' : 'visibility_off';
        button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
});
JS);
?>
