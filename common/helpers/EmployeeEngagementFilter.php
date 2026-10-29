<?php

namespace common\helpers;

use common\modules\employee\models\Employee;
use Yii;
use yii\db\Expression;
use yii\db\Query;

/**
 * Limits surveys/evaluations visible to an employee to those sent on or after their active date.
 */
class EmployeeEngagementFilter
{
    public static function employeeMayReceiveEngagement(Employee $employee): bool
    {
        return $employee->canReceiveEmployeePortalData();
    }

    /**
     * Start of the employee's active period (joining date at 00:00:00), or null if unknown.
     */
    public static function getActiveSinceDatetime(Employee $employee): ?string
    {
        if ($employee->hasAttribute('joining_date')) {
            $joining = trim((string)$employee->getAttribute('joining_date'));
            if ($joining !== '') {
                $ts = strtotime($joining);
                if ($ts !== false) {
                    return date('Y-m-d 00:00:00', $ts);
                }
            }
        }

        if ($employee->hasAttribute('created_at')) {
            $created = $employee->getAttribute('created_at');
            if ($created !== null && $created !== '') {
                if (is_numeric($created)) {
                    return date('Y-m-d H:i:s', (int)$created);
                }

                $ts = strtotime((string)$created);
                if ($ts !== false) {
                    return date('Y-m-d H:i:s', $ts);
                }
            }
        }

        return null;
    }

    /**
     * SQL condition: survey/evaluation was sent on or after the employee became active,
     * or they were explicitly targeted after that date.
     *
     * @param Query $query Query on main table (alias optional)
     * @param string $mainTable Physical table name e.g. surveys
     * @param string|null $mainAlias Table alias in query, or null to use table name
     */
    public static function applySentAfterActiveToQuery(
        Query $query,
        Employee $employee,
        string $mainTable,
        string $employeeTargetTable,
        string $foreignKey,
        ?string $mainAlias = null
    ): void {
        if (!self::employeeMayReceiveEngagement($employee)) {
            $query->andWhere('0=1');

            return;
        }

        $activeSince = self::getActiveSinceDatetime($employee);
        if ($activeSince === null) {
            return;
        }

        $main = $mainAlias ?? $mainTable;
        $schema = Yii::$app->db->schema->getTableSchema($mainTable, true);
        if ($schema === null || !isset($schema->columns['created_at'])) {
            return;
        }

        $employeeId = (int)$employee->id;
        $or = [['>=', "$main.created_at", $activeSince]];

        $targetSchema = Yii::$app->db->schema->getTableSchema($employeeTargetTable, true);
        if ($targetSchema !== null && isset($targetSchema->columns['created_at'])) {
            $or[] = [
                'exists',
                (new Query())
                    ->select(new Expression('1'))
                    ->from(["et" => $employeeTargetTable])
                    ->where("et.$foreignKey = $main.id")
                    ->andWhere([
                        'and',
                        ['et.employee_id' => $employeeId],
                        ['>=', 'et.created_at', $activeSince],
                    ]),
            ];
        }

        $query->andWhere(['or', ...$or]);
    }

    /**
     * @param object $item Survey or Evaluation ActiveRecord
     */
    public static function isSentAfterEmployeeActive(
        object $item,
        Employee $employee,
        string $employeeTargetTable,
        string $foreignKey
    ): bool {
        if (!self::employeeMayReceiveEngagement($employee)) {
            return false;
        }

        $activeSince = self::getActiveSinceDatetime($employee);
        if ($activeSince === null) {
            return true;
        }

        $activeTs = strtotime($activeSince);
        if ($activeTs === false) {
            return true;
        }

        $employeeId = (int)$employee->id;
        $itemId = (int)($item->id ?? 0);

        $targetSchema = Yii::$app->db->schema->getTableSchema($employeeTargetTable, true);
        if ($targetSchema !== null && isset($targetSchema->columns['created_at']) && $itemId > 0) {
            $targetCreated = (new Query())
                ->select('created_at')
                ->from($employeeTargetTable)
                ->where([$foreignKey => $itemId, 'employee_id' => $employeeId])
                ->scalar();
            if ($targetCreated) {
                $targetTs = strtotime((string)$targetCreated);
                if ($targetTs !== false && $targetTs >= $activeTs) {
                    return true;
                }
            }
        }

        $sentTs = self::getItemSentTimestamp($item);
        if ($sentTs === null) {
            return false;
        }

        return $sentTs >= $activeTs;
    }

    /**
     * @param object $item Survey or Evaluation ActiveRecord
     */
    private static function getItemSentTimestamp(object $item): ?int
    {
        if (method_exists($item, 'hasAttribute') && $item->hasAttribute('created_at')) {
            $created = $item->getAttribute('created_at');
            if ($created !== null && $created !== '') {
                $ts = strtotime((string)$created);
                if ($ts !== false) {
                    return $ts;
                }
            }
        }

        if (method_exists($item, 'hasAttribute') && $item->hasAttribute('start_at')) {
            $start = $item->getAttribute('start_at');
            if ($start !== null && $start !== '') {
                $ts = strtotime((string)$start);
                if ($ts !== false) {
                    return $ts;
                }
            }
        }

        return null;
    }
}
