<?php

namespace common\modules\recruitment\helpers;

use common\modules\recruitment\models\Application as JobApplication;

class RecruitmentUi
{
    /** @return string[] */
    public static function locationOptions(): array
    {
        return [
            'Main Campus (On-site)',
            'Remote',
            'Hybrid',
            'Satellite Office',
        ];
    }

    public static function timeAgo(?string $datetime): string
    {
        if ($datetime === null || trim($datetime) === '') {
            return '';
        }

        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }

        $diff = time() - $ts;
        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            return (int)floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return (int)floor($diff / 3600) . 'h ago';
        }

        return (int)floor($diff / 86400) . 'd ago';
    }

    public static function formatInterviewRating(?int $rating): string
    {
        if ($rating === null || $rating < 1 || $rating > 5) {
            return '—';
        }

        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . ' (' . $rating . '/5)';
    }

    public static function statusBadgeClass(string $status): string
    {
        $status = JobApplication::displayStatus($status);

        return match ($status) {
            JobApplication::STATUS_PENDING => 'bg-[#642754]/15 text-[#642754]',
            JobApplication::STATUS_SCREENED => 'bg-orange-100 text-orange-700',
            JobApplication::STATUS_INTERVIEWING => 'bg-blue-100 text-blue-700',
            JobApplication::STATUS_OFFERED => 'bg-emerald-100 text-emerald-700',
            JobApplication::STATUS_HIRED => 'bg-emerald-100 text-emerald-800',
            JobApplication::STATUS_REJECTED => 'bg-red-100 text-red-700',
            JobApplication::STATUS_CLOSED => 'bg-slate-200 text-slate-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public static function jobStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'published' => 'bg-emerald-100 text-emerald-700',
            'draft' => 'bg-slate-100 text-slate-700',
            'closed' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    /**
     * Timeline step state for application status page.
     *
     * @return array<int, array{key: string, label: string, sublabel: string, icon: string, state: string}>
     */
    public static function applicationTimeline(JobApplication $application): array
    {
        $status = JobApplication::displayStatusForApplicant($application);
        $steps = [
            ['key' => 'applied', 'label' => 'Applied', 'sublabel' => 'Submitted', 'icon' => 'check', 'min' => 0],
            ['key' => 'review', 'label' => 'Under Review', 'sublabel' => 'Screening', 'icon' => 'visibility', 'min' => 1],
            ['key' => 'interview', 'label' => 'Interview', 'sublabel' => 'Scheduled', 'icon' => 'event_available', 'min' => 2],
            ['key' => 'completed', 'label' => 'Completed', 'sublabel' => 'Process', 'icon' => 'task_alt', 'min' => 3],
            ['key' => 'decision', 'label' => 'Decision', 'sublabel' => 'Final', 'icon' => 'stars', 'min' => 4],
        ];

        $rank = match ($status) {
            JobApplication::STATUS_PENDING => 0,
            JobApplication::STATUS_SCREENED => 1,
            JobApplication::STATUS_INTERVIEWING => 2,
            JobApplication::STATUS_OFFERED => 3,
            JobApplication::STATUS_HIRED, JobApplication::STATUS_REJECTED, JobApplication::STATUS_CLOSED => 4,
            default => 0,
        };

        if ($status === JobApplication::STATUS_CLOSED) {
            $steps[4]['label'] = 'Closed';
            $steps[4]['sublabel'] = 'Job closed';
        } elseif ($status === JobApplication::STATUS_REJECTED) {
            $steps[4]['label'] = 'Rejected';
            $steps[4]['sublabel'] = 'Closed';
        } elseif ($status === JobApplication::STATUS_HIRED) {
            $steps[4]['label'] = 'Hired';
            $steps[4]['sublabel'] = 'Success';
        } elseif ($status === JobApplication::STATUS_OFFERED) {
            $steps[4]['label'] = 'Offered';
            $steps[4]['sublabel'] = 'Pending';
        }

        $result = [];
        foreach ($steps as $step) {
            $min = $step['min'];
            unset($step['min']);
            if ($rank > $min) {
                $step['state'] = 'done';
            } elseif ($rank === $min) {
                $step['state'] = 'current';
            } else {
                $step['state'] = 'upcoming';
            }
            $result[] = $step;
        }

        return $result;
    }
}
