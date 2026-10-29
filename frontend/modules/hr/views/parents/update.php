<?php

/**
 * @var yii\web\View $this
 * @var common\modules\finance\models\FinanceAccountParentProfile $profile
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Update Parent | CultureHR';

$childrenData = [];
foreach ($profile->children as $child) {
    $childrenData[(int)$child->child_index] = [
        'id' => (int)$child->id,
        'first_name' => (string)$child->first_name,
        'second_name' => (string)($child->second_name ?? ''),
        'third_name' => (string)($child->third_name ?? ''),
        'family_name' => (string)$child->family_name,
        'is_enrolled' => $child->hasAttribute('school_student_id') && (int)($child->school_student_id ?? 0) > 0,
    ];
}
$childrenDataJson = Json::encode($childrenData);

$this->registerJs(<<<JS
(function () {
    const form = document.getElementById('parent-update-form');
    const kidsCountField = document.getElementById('parent-kids-count');
    const kidsDocuments = document.getElementById('parent-kids-documents');
    const pendingAddPrompt = document.getElementById('parent-kids-pending-add');
    const pendingAddMessage = document.getElementById('parent-kids-pending-add-message');
    const childrenData = {$childrenDataJson} || {};
    const kidTextInputClass = 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm';
    let childSlots = [];
    let pendingTargetCount = null;

    Object.keys(childrenData)
        .map(function (key) { return parseInt(key, 10); })
        .filter(function (key) { return !Number.isNaN(key); })
        .sort(function (a, b) { return a - b; })
        .forEach(function (key) {
            const saved = childrenData[key] || {};
            childSlots.push({
                id: saved.id || 0,
                first_name: saved.first_name || '',
                second_name: saved.second_name || '',
                third_name: saved.third_name || '',
                family_name: saved.family_name || '',
                is_enrolled: !!saved.is_enrolled,
            });
        });

    if (childSlots.length === 0) {
        childSlots.push({
            id: 0,
            first_name: '',
            second_name: '',
            third_name: '',
            family_name: '',
            is_enrolled: false,
        });
    }

    function confirmedCount() {
        return childSlots.length;
    }

    function normalizeKidsCount(value) {
        const parsed = parseInt(value, 10);
        if (Number.isNaN(parsed)) {
            return confirmedCount();
        }
        return Math.max(1, Math.min(20, parsed));
    }

    function readSlotFromCard(card) {
        return {
            id: parseInt(card.getAttribute('data-child-id') || '0', 10) || 0,
            first_name: card.querySelector('[data-field="first_name"]')?.value || '',
            second_name: card.querySelector('[data-field="second_name"]')?.value || '',
            third_name: card.querySelector('[data-field="third_name"]')?.value || '',
            family_name: card.querySelector('[data-field="family_name"]')?.value || '',
            is_enrolled: card.getAttribute('data-enrolled') === '1',
        };
    }

    function syncSlotsFromDom() {
        if (!kidsDocuments) {
            return;
        }
        const cards = kidsDocuments.querySelectorAll('[data-child-card]');
        if (cards.length === 0) {
            return;
        }
        childSlots = Array.from(cards).map(readSlotFromCard);
    }

    function parentNameDefaults() {
        if (!form) {
            return { second_name: '', third_name: '', family_name: '' };
        }
        return {
            second_name: (form.querySelector('[name="parent_first_name"]')?.value || '').trim(),
            third_name: (form.querySelector('[name="parent_second_name"]')?.value || '').trim(),
            family_name: (form.querySelector('[name="parent_family_name"]')?.value || '').trim(),
        };
    }

    function applyParentNamesToSlot(slot) {
        const defaults = parentNameDefaults();
        slot.second_name = defaults.second_name;
        slot.third_name = defaults.third_name;
        slot.family_name = defaults.family_name;
        return slot;
    }

    function syncParentNamesToChildren() {
        const defaults = parentNameDefaults();
        if (!kidsDocuments) {
            return;
        }
        kidsDocuments.querySelectorAll('[data-child-card]').forEach(function (card) {
            const second = card.querySelector('[data-field="second_name"]');
            const third = card.querySelector('[data-field="third_name"]');
            const family = card.querySelector('[data-field="family_name"]');
            if (second) second.value = defaults.second_name;
            if (third) third.value = defaults.third_name;
            if (family) family.value = defaults.family_name;
        });
    }

    function updateCountField() {
        if (!kidsCountField) {
            return;
        }
        const savedCount = confirmedCount();
        const displayCount = pendingTargetCount !== null ? pendingTargetCount : savedCount;
        kidsCountField.value = String(displayCount);
        kidsCountField.min = String(savedCount);
        kidsCountField.max = '20';
        kidsCountField.classList.toggle('border-[#1e3a8a]', pendingTargetCount !== null);
        kidsCountField.classList.toggle('ring-2', pendingTargetCount !== null);
        kidsCountField.classList.toggle('ring-[#1e3a8a]/20', pendingTargetCount !== null);
    }

    function hidePendingPrompt() {
        pendingTargetCount = null;
        if (pendingAddPrompt) {
            pendingAddPrompt.hidden = true;
        }
        updateCountField();
    }

    function showPendingPrompt() {
        if (!pendingAddPrompt || !pendingAddMessage || pendingTargetCount === null) {
            return;
        }

        const addCount = pendingTargetCount - confirmedCount();
        if (addCount <= 0) {
            hidePendingPrompt();
            return;
        }

        pendingAddMessage.textContent = addCount === 1
            ? 'Add 1 new child'
            : 'Add ' + addCount + ' new children';
        pendingAddPrompt.hidden = false;
    }

    function renderKidDocuments() {
        if (!kidsDocuments || !kidsCountField) {
            return;
        }

        kidsDocuments.innerHTML = '';
        childSlots.forEach(function (slot, offset) {
            const index = offset + 1;
            const esc = function (value) {
                return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            };
            const canRemove = childSlots.length > 1 && !slot.is_enrolled;
            const card = document.createElement('div');
            card.className = 'p-4 rounded-lg border border-slate-200 bg-slate-50 mb-3 last:mb-0';
            card.setAttribute('data-child-card', '1');
            card.setAttribute('data-child-id', String(slot.id || 0));
            card.setAttribute('data-enrolled', slot.is_enrolled ? '1' : '0');
            card.innerHTML =
                '<div class="flex items-start justify-between gap-3 mb-3">' +
                    '<p class="text-sm font-bold text-[#1e3a8a] m-0">Child #' + index + '</p>' +
                    (canRemove
                        ? '<button type="button" data-remove-child="' + index + '" class="inline-flex items-center gap-1 h-8 px-2.5 rounded-lg border border-red-200 bg-white text-red-600 text-xs font-bold cursor-pointer hover:bg-red-50">' +
                            '<span class="material-symbols-outlined text-base leading-none">delete</span>Remove</button>'
                        : (slot.is_enrolled
                            ? '<span class="text-[11px] font-semibold text-slate-500">Enrolled — cannot remove here</span>'
                            : '')) +
                '</div>' +
                '<input type="hidden" name="kid_id_' + index + '" value="' + esc(slot.id || 0) + '">' +
                '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">' +
                    '<div><label class="block text-sm font-bold text-slate-700 mb-2">First Name</label>' +
                    '<input type="text" name="kid_first_name_' + index + '" data-field="first_name" value="' + esc(slot.first_name) + '" required class="' + kidTextInputClass + '"></div>' +
                    '<div><label class="block text-sm font-bold text-slate-700 mb-2">Second Name</label>' +
                    '<input type="text" name="kid_second_name_' + index + '" data-field="second_name" value="' + esc(slot.second_name) + '" class="' + kidTextInputClass + '"></div>' +
                    '<div><label class="block text-sm font-bold text-slate-700 mb-2">Third Name</label>' +
                    '<input type="text" name="kid_third_name_' + index + '" data-field="third_name" value="' + esc(slot.third_name) + '" class="' + kidTextInputClass + '"></div>' +
                    '<div><label class="block text-sm font-bold text-slate-700 mb-2">Family Name</label>' +
                    '<input type="text" name="kid_family_name_' + index + '" data-field="family_name" value="' + esc(slot.family_name) + '" required class="' + kidTextInputClass + '"></div>' +
                '</div>';
            kidsDocuments.appendChild(card);
        });

        updateCountField();
    }

    function handleKidsCountInput() {
        syncSlotsFromDom();
        const savedCount = confirmedCount();
        const requested = normalizeKidsCount(kidsCountField.value);

        if (requested <= savedCount) {
            hidePendingPrompt();
            return;
        }

        pendingTargetCount = requested;
        updateCountField();
        showPendingPrompt();
    }

    function confirmPendingAdd() {
        if (pendingTargetCount === null) {
            return;
        }

        syncSlotsFromDom();
        const target = pendingTargetCount;
        hidePendingPrompt();

        while (childSlots.length < target) {
            childSlots.push(applyParentNamesToSlot({
                id: 0,
                first_name: '',
                second_name: '',
                third_name: '',
                family_name: '',
                is_enrolled: false,
            }));
        }

        renderKidDocuments();
    }

    function cancelPendingAdd() {
        hidePendingPrompt();
    }

    kidsDocuments?.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-child]');
        if (!button) {
            return;
        }

        const index = parseInt(button.getAttribute('data-remove-child') || '0', 10);
        if (Number.isNaN(index) || index < 1) {
            return;
        }

        syncSlotsFromDom();
        if (childSlots.length <= 1) {
            return;
        }

        const slot = childSlots[index - 1];
        if (slot && slot.is_enrolled) {
            return;
        }

        if (!window.confirm('Remove Child #' + index + ' from this parent?')) {
            return;
        }

        childSlots.splice(index - 1, 1);
        hidePendingPrompt();
        renderKidDocuments();
    });

    form?.addEventListener('click', function (event) {
        if (event.target.closest('#parent-kids-pending-add-confirm')) {
            event.preventDefault();
            confirmPendingAdd();
            return;
        }
        if (event.target.closest('#parent-kids-pending-add-cancel')) {
            event.preventDefault();
            cancelPendingAdd();
        }
    });

    form?.addEventListener('submit', function (event) {
        if (pendingTargetCount !== null) {
            event.preventDefault();
            window.alert('Confirm or cancel adding children before saving the parent profile.');
        }
    });

    kidsCountField?.addEventListener('keydown', function (event) {
        if (event.key === 'Tab' || event.key === 'Shift') {
            return;
        }
        event.preventDefault();
    });
    kidsCountField?.addEventListener('paste', function (event) {
        event.preventDefault();
    });
    kidsCountField?.addEventListener('wheel', function (event) {
        event.preventDefault();
    }, { passive: false });
    kidsCountField?.addEventListener('input', handleKidsCountInput);
    kidsCountField?.addEventListener('change', handleKidsCountInput);

    ['parent_first_name', 'parent_second_name', 'parent_family_name'].forEach(function (fieldName) {
        form?.querySelector('[name="' + fieldName + '"]')?.addEventListener('input', syncParentNamesToChildren);
    });

    renderKidDocuments();
})();
JS);

$errorMessage = (string)Yii::$app->session->getFlash('error', '');

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../layouts/_navbar', ['currentPage' => $currentPage ?? 'parents_directory']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../layouts/_topbar', ['pageTitle' => 'Parents Directory', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-5xl mx-auto px-6 lg:px-8 py-8">
                <?php if ($errorMessage !== ''): ?>
                    <div class="mb-4 p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 text-sm font-medium">
                        <?= Html::encode($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-start justify-between gap-4 mb-6">
                        <div>
                            <a href="<?= Html::encode(Url::to(['view', 'id' => (int)$profile->id])) ?>"
                               class="group inline-flex items-center gap-3 no-underline mb-4 text-slate-500 hover:text-[#1e3a8a] transition-colors">
                                <span class="inline-flex items-center justify-center size-11 rounded-xl border border-slate-200 bg-slate-50 text-[#1e3a8a] shadow-sm group-hover:bg-[#1e3a8a] group-hover:text-white group-hover:border-[#1e3a8a] transition-colors">
                                    <span class="material-symbols-outlined text-[28px] leading-none">arrow_back</span>
                                </span>
                                <span class="text-sm font-bold uppercase tracking-wide">Back to Parent Profile</span>
                            </a>
                            <h1 class="text-2xl font-black text-[#1e3a8a]">Update Parent</h1>
                            <p class="text-slate-500 mt-1"><?= Html::encode($profile->getFullName()) ?></p>
                        </div>
                    </div>

                    <?= Html::beginForm(['update', 'id' => (int)$profile->id], 'post', [
                        'id' => 'parent-update-form',
                    ]) ?>
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">First Name</label>
                                <input type="text" name="parent_first_name" required value="<?= Html::encode($profile->first_name) ?>"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Second Name</label>
                                <input type="text" name="parent_second_name" value="<?= Html::encode((string)($profile->second_name ?? '')) ?>"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Third Name</label>
                                <input type="text" name="parent_third_name" value="<?= Html::encode((string)($profile->third_name ?? '')) ?>"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Family Name</label>
                                <input type="text" name="parent_family_name" required value="<?= Html::encode($profile->family_name) ?>"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Parent Phone</label>
                                <input type="tel" name="parent_phone" required value="<?= Html::encode($profile->phone) ?>"
                                       data-digits-only
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Emergency Phone</label>
                                <input type="tel" name="parent_emergency_phone_2" required value="<?= Html::encode($profile->emergency_phone_2) ?>"
                                       data-digits-only
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Contact Email (optional)</label>
                                <input type="email" name="parent_email" value="<?= Html::encode((string)($profile->email ?? '')) ?>"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Number of Children</label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <input type="number" name="parent_kids_count" id="parent-kids-count"
                                           min="<?= max(1, (int)$profile->kids_count) ?>" max="20"
                                           value="<?= max(1, (int)$profile->kids_count) ?>" required
                                           inputmode="numeric"
                                           class="w-20 h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 text-sm transition-shadow [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-auto [&::-webkit-inner-spin-button]:appearance-auto">
                                    <div id="parent-kids-pending-add" hidden
                                         class="inline-flex items-center gap-1.5 pl-2 pr-1 py-1 rounded-lg border border-[#1e3a8a]/20 bg-[#1e3a8a]/5 shadow-sm">
                                        <span class="material-symbols-outlined text-[#1e3a8a] text-[18px] leading-none">person_add</span>
                                        <span id="parent-kids-pending-add-message" class="text-xs font-semibold text-[#1e3a8a] whitespace-nowrap"></span>
                                        <button type="button" id="parent-kids-pending-add-confirm"
                                                class="inline-flex items-center h-7 px-2.5 rounded-md bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-[11px] font-bold border-0 cursor-pointer">
                                            Add
                                        </button>
                                        <button type="button" id="parent-kids-pending-add-cancel"
                                                class="inline-flex items-center justify-center h-7 w-7 rounded-md border-0 bg-transparent text-slate-500 hover:bg-white/80 hover:text-slate-700 cursor-pointer"
                                                aria-label="Cancel adding children">
                                            <span class="material-symbols-outlined text-[18px] leading-none">close</span>
                                        </button>
                                    </div>
                                </div>
                                <p id="parent-kids-count-hint" class="text-xs text-slate-500 mt-2">
                                    Use the arrows to set the count, then click <span class="font-semibold">Add</span>. To remove, use <span class="font-semibold">Remove</span> on a child card.
                                </p>
                            </div>
                        </div>

                        <div class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
                                <span class="inline-flex items-center justify-center size-8 rounded-lg bg-blue-50 text-[#1e3a8a]">
                                    <span class="material-symbols-outlined text-[18px]">lock</span>
                                </span>
                                <h3 class="text-sm font-extrabold text-[#1e3a8a] m-0">Portal Credentials</h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <p class="text-xs text-slate-500 m-0">
                                    These credentials are used to sign in to the LMS parent portal.
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[0.72rem] font-bold uppercase tracking-wide text-slate-600 mb-1.5">Login Email</label>
                                        <input type="email" readonly value="<?= Html::encode((string)($profile->user?->email ?? 'Will be generated on save if missing')) ?>"
                                               class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-600">
                                        <p class="text-xs text-slate-500 mt-2">Auto-generated from the parent name with numbers for uniqueness. Not editable here.</p>
                                    </div>
                                    <div>
                                        <label class="block text-[0.72rem] font-bold uppercase tracking-wide text-slate-600 mb-1.5">Login Password</label>
                                        <div class="flex items-center gap-2 w-full">
                                            <input type="password" id="parent-login-password" name="parent_login_password"
                                                   value="<?= Html::encode((string)($profile->login_password_plain ?? '')) ?>"
                                                   class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm font-mono"
                                                   autocomplete="new-password">
                                            <button type="button" data-password-toggle="parent-login-password"
                                                    class="shrink-0 inline-flex items-center justify-center p-1 text-slate-500 hover:text-[#1e3a8a] bg-transparent border-0 cursor-pointer"
                                                    aria-label="Show password">
                                                <span class="material-symbols-outlined text-2xl leading-none">visibility</span>
                                            </button>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-2">
                                            <?= trim((string)($profile->login_password_plain ?? '')) !== ''
                                                ? 'You can view or change this password. Leave unchanged to keep the current one.'
                                                : 'Password was changed by the parent or is unavailable. Enter a new password to reset it.' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-slate-700 mb-3">Children</h3>
                            <div id="parent-kids-documents"></div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <a href="<?= Html::encode(Url::to(['view', 'id' => (int)$profile->id])) ?>"
                               class="inline-flex items-center h-11 px-5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-bold no-underline">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold border-0 cursor-pointer">
                                <span class="material-symbols-outlined text-lg">save</span>
                                Save Changes
                            </button>
                        </div>
                    <?= Html::endForm() ?>
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
