<?php

namespace common\modules\evaluation\services;

use common\modules\employee\models\Department;
use common\modules\employee\models\Employee;
use common\modules\evaluation\models\Evaluation;
use common\modules\evaluation\models\EvaluationAnswer;
use common\modules\evaluation\models\EvaluationEmployeeTarget;
use common\modules\evaluation\models\EvaluationQuestion;
use common\modules\evaluation\models\EvaluationTarget;

/**
 * Aggregates evaluation response data for the HR results dashboard (legacy evaluation_response.php).
 */
class EvaluationResultsService
{
    /**
     * @return array{
     *   groupedResponses: array<string, array{employee_name: ?string, employee_email: ?string, submitted_at: ?string, answers: array<int, EvaluationAnswer>}>,
     *   questionStats: array<int, array>,
     *   targetDepartments: string[],
     *   targetEmployees: string[],
     *   allAnswers: EvaluationAnswer[]
     * }
     */
    public function build(Evaluation $evaluation, array $questions, string $search = ''): array
    {
        $isAnonymous = $evaluation->getIsAnonymous();

        $orderBy = ['sa.id' => SORT_DESC];
        if (EvaluationAnswer::hasColumn('created_at')) {
            $orderBy = ['sa.created_at' => SORT_DESC, 'sa.id' => SORT_DESC];
        }

        $allAnswers = EvaluationAnswer::find()
            ->alias('sa')
            ->where(['sa.evaluation_id' => $evaluation->id])
            ->with($isAnonymous ? ['question', 'response'] : ['question', 'employee', 'response'])
            ->orderBy($orderBy)
            ->all();

        $groupedResponses = $this->groupResponses($evaluation, $allAnswers, $isAnonymous);
        if (!$isAnonymous && $search !== '') {
            $groupedResponses = $this->filterBySearch($groupedResponses, $search);
        }

        $answerableQuestions = array_values(array_filter(
            $questions,
            static fn(EvaluationQuestion $q) => !in_array($q->question_type, ['section_break', 'section_title', 'image', 'video'], true)
        ));

        return [
            'groupedResponses' => $groupedResponses,
            'questionStats' => $this->buildQuestionStats($answerableQuestions, $allAnswers),
            'targetDepartments' => $evaluation->hasDepartmentTargets()
                ? $evaluation->getTargetDepartmentNames()
                : [],
            'targetEmployees' => !$evaluation->hasDepartmentTargets()
                ? $evaluation->getTargetEmployeeNames()
                : [],
            'targetLabel' => $evaluation->getTargetDisplayLabel(),
            'pendingRespondents' => $evaluation->getPendingRespondents(),
            'allAnswers' => $allAnswers,
        ];
    }

    /**
     * @param EvaluationAnswer[] $answers
     * @return array<string, array{employee_name: ?string, employee_email: ?string, submitted_at: ?string, answers: array<int, EvaluationAnswer>}>
     */
    private function groupResponses(Evaluation $evaluation, array $answers, bool $isAnonymous): array
    {
        $grouped = [];

        foreach ($answers as $answer) {
            if (!$answer->response_id) {
                continue;
            }

            $key = (string)$answer->response_id;
            if (!isset($grouped[$key])) {
                $employee = $answer->employee;
                $submittedAt = null;
                if ($answer->response && $answer->response->hasAttribute('created_at') && $answer->response->created_at) {
                    $submittedAt = $answer->response->created_at;
                } elseif ($answer->hasAttribute('created_at') && $answer->created_at) {
                    $submittedAt = $answer->created_at;
                }

                $grouped[$key] = [
                    'employee_name' => $isAnonymous ? null : ($employee->full_name ?? null),
                    'employee_email' => $isAnonymous ? null : ($employee->email ?? null),
                    'submitted_at' => $submittedAt,
                    'answers' => [],
                ];
            }

            $grouped[$key]['answers'][$answer->question_id] = $answer;
        }

        return $grouped;
    }

    /**
     * @param array<string, array{employee_name: ?string, employee_email: ?string, submitted_at: ?string, answers: array<int, EvaluationAnswer>}> $grouped
     * @return array<string, array{employee_name: ?string, employee_email: ?string, submitted_at: ?string, answers: array<int, EvaluationAnswer>}>
     */
    private function filterBySearch(array $grouped, string $search): array
    {
        $needle = mb_strtolower($search);
        $filtered = [];

        foreach ($grouped as $key => $resp) {
            $name = mb_strtolower((string)($resp['employee_name'] ?? ''));
            if ($name !== '' && str_contains($name, $needle)) {
                $filtered[$key] = $resp;
            }
        }

        return $filtered;
    }

    /**
     * @param EvaluationQuestion[] $questions
     * @param EvaluationAnswer[] $answers
     * @return array<int, array>
     */
    private function buildQuestionStats(array $questions, array $answers): array
    {
        $stats = [];

        foreach ($questions as $q) {
            $qId = $q->id;
            $qType = $q->question_type;

            if (in_array($qType, ['rating', 'linear_scale'], true)) {
                $min = $q->scale_min ? (int)$q->scale_min : 1;
                $max = $q->scale_max ? (int)$q->scale_max : 5;
                $distribution = [];
                for ($i = $min; $i <= $max; $i++) {
                    $distribution[$i] = 0;
                }
                $values = [];

                foreach ($answers as $a) {
                    if ((int)$a->question_id === (int)$qId && $a->answer_number !== null && $a->answer_number !== '') {
                        $val = (int)$a->answer_number;
                        $values[] = $val;
                        if (isset($distribution[$val])) {
                            $distribution[$val]++;
                        }
                    }
                }

                if ($values !== []) {
                    $stats[$qId] = [
                        'type' => 'rating',
                        'average' => round(array_sum($values) / count($values), 2),
                        'total' => count($values),
                        'distribution' => $distribution,
                        'scale_min' => $min,
                        'scale_max' => $max,
                    ];
                }
            } elseif (in_array($qType, ['multiple_choice', 'checkbox', 'dropdown'], true)) {
                $optionCounts = [];

                foreach ($answers as $a) {
                    if ((int)$a->question_id !== (int)$qId) {
                        continue;
                    }
                    if ($qType === 'checkbox') {
                        foreach (self::answerChoiceParts($q, $a) as $part) {
                            $optionCounts[$part] = ($optionCounts[$part] ?? 0) + 1;
                        }
                    } else {
                        $text = self::answerDisplayText($q, $a);
                        if ($text !== '' && $text !== 'N/A') {
                            $optionCounts[$text] = ($optionCounts[$text] ?? 0) + 1;
                        }
                    }
                }

                if ($optionCounts !== []) {
                    arsort($optionCounts);
                    $stats[$qId] = [
                        'type' => 'choice',
                        'counts' => $optionCounts,
                        'total' => array_sum($optionCounts),
                    ];
                }
            }
        }

        return $stats;
    }

    /** @return string[] */
    private function getTargetDepartmentNames(Evaluation $evaluation): array
    {
        return $evaluation->getTargetDepartmentNames();
    }

    /** @return string[] */
    private function getTargetEmployeeNames(Evaluation $evaluation): array
    {
        $ids = EvaluationEmployeeTarget::find()
            ->select('employee_id')
            ->where(['evaluation_id' => $evaluation->id])
            ->column();

        if ($ids === []) {
            return [];
        }

        return Employee::find()
            ->select('full_name')
            ->where(['id' => $ids])
            ->orderBy(['full_name' => SORT_ASC])
            ->column();
    }

    public static function formatDate(?string $dateStr): string
    {
        if (!$dateStr) {
            return 'Not set';
        }
        try {
            return (new \DateTime($dateStr))->format('M j, Y g:i A');
        } catch (\Exception) {
            return $dateStr;
        }
    }

    public static function questionTypeLabel(string $type): string
    {
        return [
            'short_text' => 'Short Answer',
            'long_text' => 'Paragraph',
            'multiple_choice' => 'Multiple Choice',
            'checkbox' => 'Checkboxes',
            'dropdown' => 'Dropdown',
            'rating' => 'Rating Scale',
            'linear_scale' => 'Linear Scale',
        ][$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    public static function questionTypeIcon(string $type): string
    {
        return [
            'short_text' => 'short_text',
            'long_text' => 'notes',
            'multiple_choice' => 'radio_button_checked',
            'checkbox' => 'check_box',
            'dropdown' => 'arrow_drop_down_circle',
            'rating' => 'star',
            'linear_scale' => 'linear_scale',
        ][$type] ?? 'help';
    }

    public static function statusBadgeClass(?string $status): string
    {
        return [
            'draft' => 'bg-slate-100 text-slate-600',
            'scheduled' => 'bg-amber-100 text-amber-600',
            'active' => 'bg-emerald-100 text-emerald-600',
            'closed' => 'bg-rose-100 text-rose-600',
        ][$status ?? 'draft'] ?? 'bg-slate-100 text-slate-600';
    }

    /**
     * Human-readable answer for results (handles legacy rows that stored numeric choices in answer_number).
     */
    public static function answerDisplayText(EvaluationQuestion $question, EvaluationAnswer $answer): string
    {
        $type = (string)$question->question_type;

        if (in_array($type, ['rating', 'linear_scale'], true)) {
            if ($answer->answer_number !== null && $answer->answer_number !== '') {
                return (string)(int)$answer->answer_number;
            }

            return trim((string)($answer->answer_text ?? '')) ?: 'N/A';
        }

        $text = trim((string)($answer->answer_text ?? ''));
        if ($text !== '') {
            return $text;
        }

        if ($answer->answer_number !== null && $answer->answer_number !== '') {
            return (string)$answer->answer_number;
        }

        return 'N/A';
    }

    /**
     * @return string[] Non-empty choice values for checkbox-style answers.
     */
    public static function answerChoiceParts(EvaluationQuestion $question, EvaluationAnswer $answer): array
    {
        $raw = self::answerDisplayText($question, $answer);
        if ($raw === 'N/A') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn($p) => $p !== ''));
    }
}
