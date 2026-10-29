<?php

/**
 * @var yii\web\View $this
 * @var common\modules\transport\models\TransportBus $bus
 * @var array<int, string> $areaOptions
 * @var array<int, array{id:int,label:string,search:string}> $employeeOptions
 * @var array<int, array{id:int,label:string,search:string}> $studentOptions
 * @var int[] $selectedStudentIds
 * @var string $userName
 * @var string $currentPage
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Assign Bus ' . $bus->plate_number . ' | CultureHR';

$selectedSet = array_flip($selectedStudentIds);

$this->registerCss(<<<'CSS'
.assign-field input:not([type=checkbox]),
.assign-field select,
.assign-field textarea {
    width: 100%;
}
.student-pick {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 0.85rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: background-color 0.15s, border-color 0.15s;
}
.student-pick.is-selected {
    background: #eff6ff;
    border-color: #93c5fd;
}
.student-pick input[type=checkbox] {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}
CSS);

?>
<div class="flex h-screen overflow-hidden">
    <?= $this->render('../../layouts/_navbar', ['currentPage' => $currentPage ?? 'transportation_assign_buses']) ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <?= $this->render('../../layouts/_topbar', ['pageTitle' => 'Assign Bus', 'userName' => $userName]) ?>

        <main class="flex-1 overflow-y-auto bg-slate-50">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h1 class="text-2xl font-black text-slate-900">Assign Bus: <?= Html::encode($bus->plate_number) ?></h1>
                        <p class="text-sm text-slate-500 mt-1">
                            Capacity: <?= (int)$bus->capacity ?> students. Drivers and supervisors are selected from employees.
                        </p>
                    </div>

                    <?= Html::beginForm(['assign-bus', 'id' => $bus->id], 'post', ['class' => 'p-5 space-y-6']) ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="assign-field">
                                <label class="block text-sm font-bold text-slate-700 mb-1.5" for="assign-area">Area</label>
                                <select id="assign-area" name="transport_area_id" class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
                                    <option value="">Select area...</option>
                                    <?php foreach ($areaOptions as $areaId => $areaName): ?>
                                        <option value="<?= (int)$areaId ?>" <?= (int)$bus->transport_area_id === (int)$areaId ? 'selected' : '' ?>>
                                            <?= Html::encode($areaName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="assign-field">
                                <label class="block text-sm font-bold text-slate-700 mb-1.5" for="assign-driver">Driver (Employee)</label>
                                <select id="assign-driver" name="driver_employee_id" class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
                                    <option value="">Select driver...</option>
                                    <?php foreach ($employeeOptions as $employee): ?>
                                        <option value="<?= (int)$employee['id'] ?>" <?= (int)$bus->driver_employee_id === (int)$employee['id'] ? 'selected' : '' ?>>
                                            <?= Html::encode($employee['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="assign-field md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1.5" for="assign-supervisor">Supervisor (Employee)</label>
                                <select id="assign-supervisor" name="supervisor_employee_id" class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
                                    <option value="">Select supervisor...</option>
                                    <?php foreach ($employeeOptions as $employee): ?>
                                        <option value="<?= (int)$employee['id'] ?>" <?= (int)$bus->supervisor_employee_id === (int)$employee['id'] ? 'selected' : '' ?>>
                                            <?= Html::encode($employee['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                <label class="block text-sm font-bold text-slate-700">Students</label>
                                <input type="search" id="student-search" placeholder="Search students..."
                                       class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm min-w-[14rem]">
                            </div>

                            <?php if ($studentOptions === []): ?>
                                <p class="text-sm text-slate-500 p-4 rounded-lg bg-slate-50 border border-slate-200">
                                    No students found. Add students in Student Affairs first.
                                </p>
                            <?php else: ?>
                                <div id="student-list" class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-80 overflow-y-auto">
                                    <?php foreach ($studentOptions as $student): ?>
                                        <?php
                                        $studentId = (int)$student['id'];
                                        $isSelected = isset($selectedSet[$studentId]);
                                        ?>
                                        <label class="student-pick<?= $isSelected ? ' is-selected' : '' ?>"
                                               data-search="<?= Html::encode($student['search']) ?>">
                                            <input type="checkbox" name="student_ids[]" value="<?= $studentId ?>"
                                                   <?= $isSelected ? 'checked' : '' ?>>
                                            <span class="text-sm text-slate-800"><?= Html::encode($student['label']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 h-11 px-6 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold cursor-pointer border-0">
                                Save Assignment
                            </button>
                            <a href="<?= Html::encode(Url::to(['assign-buses'])) ?>"
                               class="inline-flex items-center h-11 px-5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-bold no-underline">
                                Cancel
                            </a>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </main>
    </div>
</div>
<?php
$this->registerJs(<<<'JS'
(function () {
    const searchInput = document.getElementById('student-search');
    const picks = document.querySelectorAll('.student-pick');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            picks.forEach(function (pick) {
                const haystack = pick.getAttribute('data-search') || '';
                pick.style.display = term === '' || haystack.indexOf(term) !== -1 ? '' : 'none';
            });
        });
    }

    picks.forEach(function (pick) {
        const checkbox = pick.querySelector('input[type=checkbox]');
        if (!checkbox) {
            return;
        }
        checkbox.addEventListener('change', function () {
            pick.classList.toggle('is-selected', checkbox.checked);
        });
    });
})();
JS
, \yii\web\View::POS_END);
?>
