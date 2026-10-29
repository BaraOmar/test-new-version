<?php

use common\modules\employee\models\Employee;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\modules\employee\models\Department[] $departments */
/** @var string $search */
/** @var int $dept */
/** @var string $pos */
/** @var bool $hasStatus */

$this->title = 'Employee Directory';
$employees = $dataProvider->getModels();

function employeeStatusClass($status)
{
    $status = strtolower(trim((string)$status));
    if ($status === 'active') {
        return 'bg-success';
    }
    if ($status === 'onboarding') {
        return 'bg-warning text-dark';
    }
    if ($status === 'leave') {
        return 'bg-secondary';
    }
    if ($status === 'terminated' || $status === 'inactive') {
        return 'bg-danger';
    }

    return 'bg-secondary';
}
?>

<div class="employee-index py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">Manage employees, reporting structure, and employee status.</p>
        </div>
        <?= Html::a('Add Employee', ['update'], ['class' => 'btn btn-primary']) ?>
    </div>

    <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index'], 'options' => ['class' => 'card card-body mb-4']]); ?>
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label" for="employee-search">Search</label>
            <?= Html::textInput('search', $search, ['id' => 'employee-search', 'class' => 'form-control', 'placeholder' => 'Search by name, email, or role']) ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="employee-dept">Department</label>
            <select id="employee-dept" name="dept" class="form-select">
                <option value="0">All departments</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= (int)$department->id ?>" <?= (int)$department->id === (int)$dept ? 'selected' : '' ?>>
                        <?= Html::encode($department->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="employee-pos">Position</label>
            <?= Html::dropDownList('pos', $pos, [
                'all' => 'All',
                'l1' => 'l1',
                'l2' => 'l2',
                'l3' => 'l3',
                'l4' => 'l4',
                'l5' => 'l5',
            ], ['id' => 'employee-pos', 'class' => 'form-select']) ?>
        </div>
        <div class="col-md-2 d-grid">
            <?= Html::submitButton('Filter', ['class' => 'btn btn-outline-primary']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Job Title</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Manager</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$employees): ?>
                        <tr>
                            <td colspan="8" class="text-muted py-4">No employees found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($employees as $employee): ?>
                        <?php /** @var Employee $employee */ ?>
                        <tr>
                            <td>
                                <strong><?= Html::encode($employee->full_name) ?></strong>
                            </td>
                            <td><?= Html::encode($employee->job_title) ?></td>
                            <td><?= Html::encode($employee->email) ?></td>
                            <td><?= Html::encode($employee->department->name ?? '—') ?></td>
                            <td><?= Html::encode($employee->manager->full_name ?? '—') ?></td>
                            <td><?= Html::encode($employee->joining_date ?: '—') ?></td>
                            <td>
                                <span class="badge <?= employeeStatusClass($employee->getDisplayStatus()) ?>">
                                    <?= Html::encode($employee->getDisplayStatus()) ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <?= Html::a('View', ['view', 'id' => $employee->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                                <?= Html::a('Edit', ['update', 'id' => $employee->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                <?= Html::beginForm(['set-manager', 'id' => $employee->id], 'post', ['class' => 'd-inline']) ?>
                                    <?= Html::submitButton('Set Manager', ['class' => 'btn btn-sm btn-outline-success']) ?>
                                <?= Html::endForm() ?>
                                <?= Html::beginForm(['delete', 'id' => $employee->id], 'post', ['class' => 'd-inline']) ?>
                                    <?= Html::submitButton('Delete', [
                                        'class' => 'btn btn-sm btn-outline-danger',
                                        'data-confirm' => 'Are you sure you want to delete this employee?',
                                    ]) ?>
                                <?= Html::endForm() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted">
            Showing <?= count($employees) ?> employee<?= count($employees) === 1 ? '' : 's' ?>.
        </div>
    </div>
</div>
