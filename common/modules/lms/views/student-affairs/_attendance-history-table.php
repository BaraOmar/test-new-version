<?php

/**
 * @var yii\web\View $this
 * @var array<int, array<string, mixed>> $rows
 * @var yii\data\Pagination $pagination
 * @var string $startDate
 * @var string $endDate
 * @var array $formAction
 * @var array $paginationParams
 */

use common\modules\employee\models\SchoolStudentAttendanceRecord;
use yii\helpers\Html;
use yii\widgets\LinkPager;

$totalCount = (int)$pagination->totalCount;
$from = $totalCount > 0 ? $pagination->offset + 1 : 0;
$to = min($pagination->offset + count($rows), $totalCount);

$statusBadgeClass = static function (string $status): string {
    return match ($status) {
        SchoolStudentAttendanceRecord::STATUS_ABSENT => 'bg-red-50 text-red-700',
        SchoolStudentAttendanceRecord::STATUS_LATE => 'bg-sky-50 text-sky-700',
        SchoolStudentAttendanceRecord::STATUS_EXCUSED => 'bg-amber-50 text-amber-700',
        default => 'bg-emerald-50 text-emerald-700',
    };
};

$statusDotClass = static function (string $status): string {
    return match ($status) {
        SchoolStudentAttendanceRecord::STATUS_ABSENT => 'bg-red-500',
        SchoolStudentAttendanceRecord::STATUS_LATE => 'bg-sky-500',
        SchoolStudentAttendanceRecord::STATUS_EXCUSED => 'bg-amber-500',
        default => 'bg-emerald-500',
    };
};

$this->registerCss(<<<'CSS'
.attendance-history-table th { letter-spacing: 0.08em; }
.attendance-history-table tbody tr:hover { background: #f8fafc; }
.attendance-history-pager .pagination { display: flex; flex-wrap: wrap; gap: 0.35rem; list-style: none; margin: 0; padding: 0; }
.attendance-history-pager .pagination > li > a,
.attendance-history-pager .pagination > li > span {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2.25rem; height: 2.25rem; padding: 0 0.65rem;
    border-radius: 0.5rem; border: 1px solid #e2e8f0;
    font-size: 0.875rem; font-weight: 600; text-decoration: none;
}
.attendance-history-pager .pagination > li > a { color: #334155; background: #fff; }
.attendance-history-pager .pagination > li > a:hover { background: #f8fafc; }
.attendance-history-pager .pagination > .active > a,
.attendance-history-pager .pagination > .active > span {
    background: #1e3a8a; border-color: #1e3a8a; color: #fff;
}
.attendance-history-pager .pagination > .disabled > span { color: #cbd5e1; background: #f8fafc; }
CSS
);

?>
<section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 bg-white">
        <?= Html::beginForm($formAction, 'get', ['class' => 'flex flex-wrap items-end gap-4']) ?>
            <div class="min-w-[10rem]">
                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Start Date</label>
                <input type="date" name="start_date" value="<?= Html::encode($startDate) ?>"
                       max="<?= Html::encode(date('Y-m-d')) ?>"
                       class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700">
            </div>
            <div class="min-w-[10rem]">
                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">End Date</label>
                <input type="date" name="end_date" value="<?= Html::encode($endDate) ?>"
                       max="<?= Html::encode(date('Y-m-d')) ?>"
                       class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700">
            </div>
            <div class="ml-auto">
                <button type="submit" class="inline-flex items-center gap-2 h-10 px-5 rounded-lg bg-[#1e3a8a] hover:bg-[#1e40af] text-white text-sm font-bold border-0 cursor-pointer">
                    <span class="material-symbols-outlined text-base">filter_alt</span>
                    Filter
                </button>
            </div>
        <?= Html::endForm() ?>
    </div>

    <?php if ($rows === []): ?>
        <div class="p-12 text-center">
            <span class="material-symbols-outlined text-5xl text-slate-300">event_busy</span>
            <p class="text-sm text-slate-500 mt-4">No attendance records found for the selected date range.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="attendance-history-table w-full text-sm border-collapse">
                <thead>
                    <tr class="text-left text-[11px] font-bold uppercase text-slate-400 border-b border-slate-200 bg-slate-50">
                        <th class="px-5 py-3.5">Date</th>
                        <th class="px-5 py-3.5">Day</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Time In</th>
                        <th class="px-5 py-3.5">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $status = (string)($row['status'] ?? ''); ?>
                        <tr class="border-b border-slate-100">
                            <td class="px-5 py-4 font-semibold text-slate-900 whitespace-nowrap"><?= Html::encode((string)($row['date_label'] ?? '')) ?></td>
                            <td class="px-5 py-4 text-slate-600 whitespace-nowrap"><?= Html::encode((string)($row['day_label'] ?? '')) ?></td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold <?= Html::encode($statusBadgeClass($status)) ?>">
                                    <span class="size-2 rounded-full shrink-0 <?= Html::encode($statusDotClass($status)) ?>"></span>
                                    <?= Html::encode((string)($row['status_label'] ?? '')) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600 whitespace-nowrap font-medium"><?= Html::encode((string)($row['time_in_label'] ?? '--:-- --')) ?></td>
                            <td class="px-5 py-4 text-slate-500 italic"><?= Html::encode((string)($row['remarks'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500 m-0">
                Showing <?= (int)$from ?> to <?= (int)$to ?> of <?= (int)$totalCount ?> entries
            </p>
            <div class="attendance-history-pager">
                <?php
                $pagination->params = $paginationParams;
                echo LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination'],
                ]);
                ?>
            </div>
        </div>
    <?php endif; ?>
</section>
