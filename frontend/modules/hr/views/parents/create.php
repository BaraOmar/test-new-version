<?php

/**
 * @var yii\web\View $this
 * @var string $userName
 * @var string $currentPage
 * @var array<string, mixed> $posted
 * @var string $generatedPassword
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Add Parent | CultureHR';

$posted = is_array($posted ?? null) ? $posted : [];
$postedKidsCount = max(0, min(20, (int)($posted['parent_kids_count'] ?? 0)));
$childrenData = [];
for ($index = 1; $index <= $postedKidsCount; $index++) {
    $childrenData[$index] = [
        'first_name' => (string)($posted['kid_first_name_' . $index] ?? ''),
        'second_name' => (string)($posted['kid_second_name_' . $index] ?? ''),
        'third_name' => (string)($posted['kid_third_name_' . $index] ?? ''),
        'family_name' => (string)($posted['kid_family_name_' . $index] ?? ''),
    ];
}
$childrenDataJson = Json::encode($childrenData);
$errorMessage = (string)Yii::$app->session->getFlash('error', '');
$generatedPassword = (string)($generatedPassword ?? '');

$this->registerJs(<<<JS
(function () {
    const kidsCountField = document.getElementById('parent-kids-count');
    const kidsDocuments = document.getElementById('parent-kids-documents');
    const childrenData = {$childrenDataJson} || {};
    const kidTextInputClass = 'w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm';
    const createForm = document.getElementById('parent-create-form');
    const submitBtn = createForm ? createForm.querySelector('button[type="submit"]') : null;
    const kidsCountHint = document.getElementById('parent-kids-count-hint');

    function normalizeKidsCount(value) {
        const parsed = parseInt(value, 10);
        if (Number.isNaN(parsed)) {
            return 0;
        }
        return Math.max(0, Math.min(20, parsed));
    }

    function syncSubmitState() {
        if (!kidsCountField || !submitBtn) {
            return;
        }
        const count = normalizeKidsCount(kidsCountField.value);
        const blocked = count < 1;
        submitBtn.disabled = blocked;
        if (kidsCountHint) {
            kidsCountHint.hidden = !blocked;
        }
    }

    function parentNameDefaults() {
        if (!createForm) {
            return { second_name: '', third_name: '', family_name: '' };
        }
        return {
            second_name: (createForm.querySelector('[name="parent_first_name"]')?.value || '').trim(),
            third_name: (createForm.querySelector('[name="parent_second_name"]')?.value || '').trim(),
            family_name: (createForm.querySelector('[name="parent_family_name"]')?.value || '').trim(),
        };
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

    function renderKidDocuments() {
        if (!kidsDocuments || !kidsCountField) {
            return;
        }

        const existingValues = {};
        kidsDocuments.querySelectorAll('input[name^="kid_"]').forEach((input) => {
            existingValues[input.name] = input.value;
        });

        const count = normalizeKidsCount(kidsCountField.value);
        kidsCountField.value = String(count);
        kidsDocuments.innerHTML = '';
        syncSubmitState();

        if (count === 0) {
            const empty = document.createElement('p');
            empty.className = 'text-sm text-slate-500';
            empty.textContent = 'Enter at least 1 child to create this parent.';
            kidsDocuments.appendChild(empty);
            return;
        }

        for (let index = 1; index <= count; index += 1) {
            const saved = childrenData[index] || {};
            const defaults = parentNameDefaults();
            const getValue = (field) => {
                const key = 'kid_' + field + '_' + index;
                if (existingValues[key] !== undefined) {
                    return existingValues[key];
                }
                if (field === 'first_name') return saved.first_name || '';
                if (field === 'second_name') return saved.second_name || defaults.second_name;
                if (field === 'third_name') return saved.third_name || defaults.third_name;
                if (field === 'family_name') return saved.family_name || defaults.family_name;
                return '';
            };
            const esc = (value) => String(value).replace(/"/g, '&quot;');

            const card = document.createElement('div');
            card.className = 'p-4 rounded-lg border border-slate-200 bg-slate-50 mb-3 last:mb-0';
            card.setAttribute('data-child-card', '1');
            card.innerHTML =
                '<p class="text-sm font-bold text-[#1e3a8a] mb-3">Child #' + index + '</p>' +
                '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">' +
                    '<div><label class="block text-sm font-bold text-slate-700 mb-2">First Name</label>' +
                    '<input type="text" name="kid_first_name_' + index + '" data-field="first_name" value="' + esc(getValue('first_name')) + '" required class="' + kidTextInputClass + '"></div>' +
                    '<div><label class="block text-sm font-bold text-slate-700 mb-2">Second Name</label>' +
                    '<input type="text" name="kid_second_name_' + index + '" data-field="second_name" value="' + esc(getValue('second_name')) + '" class="' + kidTextInputClass + '"></div>' +
                    '<div><label class="block text-sm font-bold text-slate-700 mb-2">Third Name</label>' +
                    '<input type="text" name="kid_third_name_' + index + '" data-field="third_name" value="' + esc(getValue('third_name')) + '" class="' + kidTextInputClass + '"></div>' +
                    '<div><label class="block text-sm font-bold text-slate-700 mb-2">Family Name</label>' +
                    '<input type="text" name="kid_family_name_' + index + '" data-field="family_name" value="' + esc(getValue('family_name')) + '" required class="' + kidTextInputClass + '"></div>' +
                '</div>';
            kidsDocuments.appendChild(card);
        }
    }

    kidsCountField?.addEventListener('input', renderKidDocuments);
    kidsCountField?.addEventListener('change', renderKidDocuments);

    ['parent_first_name', 'parent_second_name', 'parent_family_name'].forEach(function (fieldName) {
        createForm?.querySelector('[name="' + fieldName + '"]')?.addEventListener('input', syncParentNamesToChildren);
    });

    createForm?.addEventListener('submit', function (event) {
        if (!kidsCountField) {
            return;
        }
        if (normalizeKidsCount(kidsCountField.value) < 1) {
            event.preventDefault();
            syncSubmitState();
        }
    });
    renderKidDocuments();
})();
JS);

$val = static function (array $posted, string $key, string $default = '') : string {
    return Html::encode((string)($posted[$key] ?? $default));
};

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
                            <a href="<?= Html::encode(Url::to(['index'])) ?>"
                               class="group inline-flex items-center gap-3 no-underline mb-4 text-slate-500 hover:text-[#1e3a8a] transition-colors">
                                <span class="inline-flex items-center justify-center size-11 rounded-xl border border-slate-200 bg-slate-50 text-[#1e3a8a] shadow-sm group-hover:bg-[#1e3a8a] group-hover:text-white group-hover:border-[#1e3a8a] transition-colors">
                                    <span class="material-symbols-outlined text-[28px] leading-none">arrow_back</span>
                                </span>
                                <span class="text-sm font-bold uppercase tracking-wide">Back to Parents Directory</span>
                            </a>
                            <h1 class="text-2xl font-black text-[#1e3a8a]">Add Parent</h1>
                            <p class="text-slate-500 mt-1">Register a parent and their children. LMS login email and password are generated automatically from the parent name.</p>
                        </div>
                    </div>

                    <?= Html::beginForm(['create'], 'post', [
                        'id' => 'parent-create-form',
                    ]) ?>
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">First Name</label>
                                <input type="text" name="parent_first_name" required value="<?= $val($posted, 'parent_first_name') ?>"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Second Name</label>
                                <input type="text" name="parent_second_name" value="<?= $val($posted, 'parent_second_name') ?>"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Third Name</label>
                                <input type="text" name="parent_third_name" value="<?= $val($posted, 'parent_third_name') ?>"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Family Name</label>
                                <input type="text" name="parent_family_name" required value="<?= $val($posted, 'parent_family_name') ?>"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Parent Phone</label>
                                <input type="tel" name="parent_phone" required value="<?= $val($posted, 'parent_phone') ?>"
                                       data-digits-only
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Emergency Phone</label>
                                <input type="tel" name="parent_emergency_phone_2" required value="<?= $val($posted, 'parent_emergency_phone_2') ?>"
                                       data-digits-only
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Contact Email (optional)</label>
                                <input type="email" name="parent_email" value="<?= $val($posted, 'parent_email') ?>"
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Number of Children</label>
                                <input type="number" name="parent_kids_count" id="parent-kids-count" min="0" max="20"
                                       value="<?= (int)$postedKidsCount ?>" required
                                       class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                                <p id="parent-kids-count-hint" class="text-xs text-red-600 mt-2 font-semibold" <?= $postedKidsCount >= 1 ? 'hidden' : '' ?>>
                                    Add at least one child before creating this parent.
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
                                    Login email is generated automatically from the parent first and family name with numbers to keep every account unique.
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[0.72rem] font-bold uppercase tracking-wide text-slate-600 mb-1.5">Login Email</label>
                                        <input type="text" readonly
                                               value="first.family.{numbers}@parents.culturehr.local"
                                               class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-500">
                                        <p class="text-xs text-slate-500 mt-2">Generated automatically when you create the parent.</p>
                                    </div>
                                    <div>
                                        <label class="block text-[0.72rem] font-bold uppercase tracking-wide text-slate-600 mb-1.5">Login Password</label>
                                        <div class="flex items-center gap-2 w-full">
                                            <input type="password" id="parent-login-password" name="parent_login_password" readonly
                                                   value="<?= Html::encode($generatedPassword) ?>"
                                                   class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm font-mono text-slate-700">
                                            <button type="button" data-password-toggle="parent-login-password"
                                                    class="shrink-0 inline-flex items-center justify-center p-1 text-slate-500 hover:text-[#1e3a8a] bg-transparent border-0 cursor-pointer"
                                                    aria-label="Show password">
                                                <span class="material-symbols-outlined text-2xl leading-none">visibility</span>
                                            </button>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-2">Auto-generated. You can view it here; it will be saved when you create the parent.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-slate-700 mb-3">Children</h3>
                            <div id="parent-kids-documents"></div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <a href="<?= Html::encode(Url::to(['index'])) ?>"
                               class="inline-flex items-center h-11 px-5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-bold no-underline">
                                Cancel
                            </a>
                            <button type="submit"
                                    id="parent-create-submit"
                                    class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold border-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    <?= $postedKidsCount < 1 ? 'disabled' : '' ?>>
                                <span class="material-symbols-outlined text-lg">person_add</span>
                                Create Parent
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
