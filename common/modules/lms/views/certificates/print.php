<?php

/**
 * @var array<string, mixed> $data
 */

use yii\helpers\Html;

$student = is_array($data['student'] ?? null) ? $data['student'] : [];
$subjects = is_array($data['subjects'] ?? null) ? $data['subjects'] : [];
$title = (string)($data['title'] ?? 'Academic Report Card');
$schoolName = (string)($data['school_name'] ?? '');
$schoolTagline = (string)($data['school_tagline'] ?? '');
$scopeLabel = (string)($data['scope_label'] ?? '');
$sessionLabel = (string)($data['academic_session_label'] ?? '');
$rank = $data['rank'] ?? false;
$sectionSize = (int)($data['section_size'] ?? 0);
$generatedOn = (string)($data['generated_on'] ?? '');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= Html::encode($title) ?></title>
    <style type="text/css">
        @page {
            size: A4 portrait;
            margin: 14mm 14mm 16mm 14mm;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #0f172a;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            min-height: 100vh;
        }
        .cert-sheet {
            width: 100%;
            max-width: 210mm;
            min-height: calc(297mm - 30mm);
            margin: 0 auto;
            padding: 8mm 6mm 6mm;
            display: flex;
            flex-direction: column;
        }
        .cert-brand-bar {
            height: 5px;
            background: linear-gradient(90deg, #1e3a5f 0%, #2f5f98 55%, #c9a227 100%);
            margin: 0 0 10mm;
        }
        .cert-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12mm;
            margin-bottom: 7mm;
            padding-bottom: 5mm;
            border-bottom: 1.5px solid #1e3a5f;
        }
        .cert-school {
            font-size: 22pt;
            font-weight: 800;
            color: #1e3a5f;
            letter-spacing: 0.02em;
            line-height: 1.15;
        }
        .cert-tagline {
            margin-top: 2mm;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #64748b;
        }
        .cert-title-block {
            text-align: right;
            max-width: 88mm;
        }
        .cert-title {
            font-size: 14pt;
            font-weight: 800;
            color: #1e3a5f;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .cert-meta {
            margin-top: 2mm;
            font-size: 9pt;
            line-height: 1.4;
            color: #475569;
        }
        .cert-student {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 4mm 8mm;
            padding: 5mm 5.5mm;
            margin-bottom: 7mm;
            background: #f5f7fb;
            border: 1px solid #d7dee8;
        }
        .cert-student-label {
            font-size: 7.5pt;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 1.2mm;
        }
        .cert-student-name {
            font-size: 13pt;
            font-weight: 800;
            color: #0f172a;
        }
        .cert-student-value {
            font-size: 10.5pt;
            font-weight: 600;
            color: #1e293b;
        }
        .cert-table-wrap {
            flex: 1 1 auto;
        }
        table.cert-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10pt;
        }
        .cert-table thead th {
            background: #1e3a5f;
            color: #fff;
            text-align: left;
            padding: 3.2mm 3mm;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .cert-table thead th.center,
        .cert-table td.center {
            text-align: center;
        }
        .cert-table tbody td {
            padding: 3.4mm 3mm;
            border-bottom: 1px solid #dbe3ee;
            vertical-align: middle;
            color: #1e293b;
        }
        .cert-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }
        .cert-table .subject {
            font-weight: 700;
            width: 28%;
        }
        .cert-table .marks { width: 22%; }
        .cert-table .grade { width: 12%; font-weight: 800; }
        .cert-table .remarks { width: 38%; color: #475569; }
        .cert-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4mm;
            margin-top: 7mm;
        }
        .cert-metric {
            border: 1px solid #cfd8e6;
            padding: 4mm 4.2mm;
            background: #fff;
        }
        .cert-metric-label {
            font-size: 7.5pt;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 1.5mm;
        }
        .cert-metric-value {
            font-size: 14pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
        }
        .cert-footer {
            margin-top: auto;
            padding-top: 10mm;
        }
        .cert-signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8mm;
            margin-bottom: 7mm;
        }
        .cert-sign {
            text-align: center;
            padding-top: 12mm;
            border-top: 1px solid #94a3b8;
            font-size: 8.5pt;
            color: #475569;
            font-weight: 600;
        }
        .cert-note {
            border-top: 1px solid #e2e8f0;
            padding-top: 3.5mm;
            font-size: 8pt;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            gap: 4mm;
        }
        @media screen {
            body {
                background: #e8edf3;
                padding: 10mm 0;
            }
            .cert-sheet {
                background: #fff;
                box-shadow: 0 8px 28px rgba(15, 23, 42, 0.12);
            }
        }
        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
            }
            .cert-sheet {
                max-width: none;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="cert-sheet">
        <div class="cert-brand-bar"></div>

        <div class="cert-header">
            <div>
                <div class="cert-school"><?= Html::encode($schoolName) ?></div>
                <div class="cert-tagline"><?= Html::encode($schoolTagline) ?></div>
            </div>
            <div class="cert-title-block">
                <div class="cert-title"><?= Html::encode($title) ?></div>
                <div class="cert-meta">
                    <?= Html::encode($scopeLabel) ?>
                    <?php if ($sessionLabel !== ''): ?>
                        <br><?= Html::encode($sessionLabel) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="cert-student">
            <div>
                <div class="cert-student-label">Student</div>
                <div class="cert-student-name"><?= Html::encode((string)($student['name'] ?? '')) ?></div>
            </div>
            <div>
                <div class="cert-student-label">Class</div>
                <div class="cert-student-value"><?= Html::encode((string)($student['class_label'] ?? '')) ?></div>
            </div>
            <div>
                <div class="cert-student-label">Registration No.</div>
                <div class="cert-student-value"><?= Html::encode((string)($student['student_code'] ?? '') !== '' ? $student['student_code'] : '—') ?></div>
            </div>
            <div>
                <div class="cert-student-label">Section Rank</div>
                <div class="cert-student-value">
                    <?php if ($rank): ?>
                        <?= Html::encode((string)$rank) ?> / <?= Html::encode((string)$sectionSize) ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="cert-table-wrap">
            <table class="cert-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th class="center">Marks</th>
                        <th class="center">Grade</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($subjects === []): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:#64748b; padding:8mm;">
                                No subjects available for this period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $row): ?>
                            <tr>
                                <td class="subject"><?= Html::encode((string)($row['subject_name'] ?? '')) ?></td>
                                <td class="marks center"><?= Html::encode((string)($row['marks_label'] ?? '')) ?></td>
                                <td class="grade center"><?= Html::encode((string)($row['grade'] ?? '')) ?></td>
                                <td class="remarks"><?= Html::encode((string)($row['remarks'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="cert-summary">
            <div class="cert-metric">
                <div class="cert-metric-label">Aggregate</div>
                <div class="cert-metric-value"><?= Html::encode((string)($data['aggregate_label'] ?? '—')) ?></div>
            </div>
            <div class="cert-metric">
                <div class="cert-metric-label">Average</div>
                <div class="cert-metric-value"><?= Html::encode((string)($data['average_label'] ?? '—')) ?></div>
            </div>
            <div class="cert-metric">
                <div class="cert-metric-label">Attendance</div>
                <div class="cert-metric-value"><?= Html::encode((string)($data['attendance_label'] ?? '—')) ?></div>
            </div>
        </div>

        <div class="cert-footer">
            <div class="cert-signatures">
                <div class="cert-sign">Class Teacher</div>
                <div class="cert-sign">Department Head</div>
                <div class="cert-sign">School Stamp / Principal</div>
            </div>
            <div class="cert-note">
                <span>
                    Generated on <?= Html::encode($generatedOn) ?>
                    · Official academic report
                </span>
                <span><?= Html::encode($schoolName) ?></span>
            </div>
        </div>
    </div>
</body>
</html>
