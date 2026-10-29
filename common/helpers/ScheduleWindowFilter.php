<?php

namespace common\helpers;

use yii\db\ActiveQuery;
use yii\db\Expression;

/**
 * Keeps only rows whose schedule date (due/end, else start, else created) falls inside the filter range.
 */
class ScheduleWindowFilter
{
    /**
     * @param class-string<\yii\db\ActiveRecord> $modelClass
     * @return array{0: string, 1: string} Normalized date_from, date_to (Y-m-d)
     */
    public static function apply(ActiveQuery $query, string $alias, string $modelClass, string $dateFrom, string $dateTo): array
    {
        [$dateFrom, $dateTo] = self::normalizeRange($dateFrom, $dateTo);

        if ($dateFrom === '' && $dateTo === '') {
            return [$dateFrom, $dateTo];
        }

        $scheduleDate = self::scheduleDateExpression($alias, $modelClass);
        if ($scheduleDate === null) {
            return [$dateFrom, $dateTo];
        }

        if ($dateFrom !== '' && $dateTo !== '') {
            $query->andWhere([
                'between',
                $scheduleDate,
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ]);
        } elseif ($dateFrom !== '') {
            $query->andWhere(['>=', $scheduleDate, $dateFrom . ' 00:00:00']);
        } elseif ($dateTo !== '') {
            $query->andWhere(['<=', $scheduleDate, $dateTo . ' 23:59:59']);
        }

        return [$dateFrom, $dateTo];
    }

    /**
     * Due/close date when set; otherwise start; otherwise created (must fall inside From–To).
     *
     * @param class-string<\yii\db\ActiveRecord> $modelClass
     */
    private static function scheduleDateExpression(string $alias, string $modelClass): Expression|string|null
    {
        $hasEnd = $modelClass::hasColumn('end_at');
        $hasStart = $modelClass::hasColumn('start_at');
        $hasCreated = $modelClass::hasColumn('created_at');

        if ($hasEnd && $hasStart && $hasCreated) {
            return new Expression("COALESCE([[{$alias}]].[[end_at]], [[{$alias}]].[[start_at]], [[{$alias}]].[[created_at]])");
        }
        if ($hasEnd && $hasStart) {
            return new Expression("COALESCE([[{$alias}]].[[end_at]], [[{$alias}]].[[start_at]])");
        }
        if ($hasEnd) {
            return "{$alias}.end_at";
        }
        if ($hasStart) {
            return "{$alias}.start_at";
        }
        if ($hasCreated) {
            return "{$alias}.created_at";
        }

        return null;
    }

    /** @return array{0: string, 1: string} */
    public static function normalizeRange(string $dateFrom, string $dateTo): array
    {
        $dateFrom = self::normalizeDate($dateFrom);
        $dateTo = self::normalizeDate($dateTo);

        if ($dateFrom !== '' && $dateTo !== '' && strcmp($dateFrom, $dateTo) > 0) {
            return [$dateTo, $dateFrom];
        }

        return [$dateFrom, $dateTo];
    }

    private static function normalizeDate(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        $ts = strtotime($date);

        return $ts === false ? '' : date('Y-m-d', $ts);
    }
}
