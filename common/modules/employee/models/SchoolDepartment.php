<?php

namespace common\modules\employee\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property int|null $head_id
 * @property int|null $deputy_head_id
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolDepartment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_department}}';
    }

    public static function isEnabled(): bool
    {
        return Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'trim'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'unique', 'message' => 'A school department with this name already exists.'],
            [['head_id', 'deputy_head_id'], 'filter', 'filter' => static function ($value) {
                if ($value === '' || $value === null) {
                    return null;
                }

                return (int)$value;
            }],
            [['head_id', 'deputy_head_id'], 'integer', 'skipOnEmpty' => true],
            [['head_id'], 'exist', 'targetClass' => Employee::class, 'targetAttribute' => ['head_id' => 'id'], 'skipOnEmpty' => true],
            [['head_id'], 'validateUniqueHead'],
            [['head_id'], 'validateHeadNotDeputyElsewhere'],
            [['head_id'], 'validateLeadershipDepartmentMembership'],
            [['deputy_head_id'], 'exist', 'targetClass' => Employee::class, 'targetAttribute' => ['deputy_head_id' => 'id'], 'skipOnEmpty' => true],
            [['deputy_head_id'], 'validateDeputyHead'],
            [['deputy_head_id'], 'validateUniqueDeputy'],
            [['deputy_head_id'], 'validateDeputyNotHeadElsewhere'],
            [['deputy_head_id'], 'validateLeadershipDepartmentMembership'],
            [['status'], 'default', 'value' => 'active'],
            [['status'], 'in', 'range' => ['active', 'inactive']],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Department Name',
            'head_id' => 'Head of Department / Dean',
            'deputy_head_id' => 'Deputy Head',
            'status' => 'Status',
        ];
    }

    public function validateDeputyHead($attribute): void
    {
        if ($this->head_id && $this->deputy_head_id && (int)$this->head_id === (int)$this->deputy_head_id) {
            $this->addError($attribute, 'Deputy head must be different from the department head.');
        }
    }

    public function validateLeadershipDepartmentMembership(string $attribute): void
    {
        $employeeId = (int)($this->$attribute ?? 0);
        if ($employeeId <= 0 || (int)$this->id <= 0) {
            return;
        }

        if (!Employee::hasColumn('school_department_id')) {
            return;
        }

        $employee = Employee::findOne(['id' => $employeeId]);
        if ($employee === null) {
            return;
        }

        if ((int)($employee->school_department_id ?? 0) !== (int)$this->id) {
            $this->addError(
                $attribute,
                'Selected employee must belong to this department before they can be assigned as head or deputy.'
            );
        }
    }

    public function validateUniqueHead($attribute): void
    {
        if (!$this->head_id) {
            return;
        }

        $existing = $this->findOtherDepartmentByAttribute('head_id', (int)$this->head_id);
        if ($existing === null) {
            return;
        }

        $this->addError(
            $attribute,
            $this->leadershipConflictMessage(
                'head',
                $existing,
                'This employee is already head of "%s". A dean can lead only one department.'
            )
        );
    }

    public function validateHeadNotDeputyElsewhere($attribute): void
    {
        if (!$this->head_id) {
            return;
        }

        $existing = $this->findOtherDepartmentByAttribute('deputy_head_id', (int)$this->head_id);
        if ($existing === null) {
            return;
        }

        $this->addError(
            $attribute,
            $this->leadershipConflictMessage(
                'deputy',
                $existing,
                'This employee is already deputy head of "%s" and cannot also lead another department.'
            )
        );
    }

    public function validateUniqueDeputy($attribute): void
    {
        if (!$this->deputy_head_id) {
            return;
        }

        $existing = $this->findOtherDepartmentByAttribute('deputy_head_id', (int)$this->deputy_head_id);
        if ($existing === null) {
            return;
        }

        $this->addError(
            $attribute,
            $this->leadershipConflictMessage(
                'deputy',
                $existing,
                'This employee is already deputy head of "%s". A deputy can serve only one department.'
            )
        );
    }

    public function validateDeputyNotHeadElsewhere($attribute): void
    {
        if (!$this->deputy_head_id) {
            return;
        }

        $existing = $this->findOtherDepartmentByAttribute('head_id', (int)$this->deputy_head_id);
        if ($existing === null) {
            return;
        }

        $this->addError(
            $attribute,
            $this->leadershipConflictMessage(
                'head',
                $existing,
                'This employee is already head of "%s" and cannot also be deputy in another department.'
            )
        );
    }

    /**
     * Employee IDs already assigned as head of another school department.
     *
     * @return int[]
     */
    public static function occupiedHeadEmployeeIds(?int $exceptDepartmentId = null): array
    {
        return static::occupiedLeadershipEmployeeIds('head_id', $exceptDepartmentId);
    }

    /**
     * Employee IDs already assigned as deputy head of another school department.
     *
     * @return int[]
     */
    public static function occupiedDeputyEmployeeIds(?int $exceptDepartmentId = null): array
    {
        return static::occupiedLeadershipEmployeeIds('deputy_head_id', $exceptDepartmentId);
    }

    /**
     * Employee IDs with any leadership role in another school department.
     *
     * @return int[]
     */
    public static function occupiedLeadershipEmployeeIds(?string $attribute = null, ?int $exceptDepartmentId = null): array
    {
        if (!static::isEnabled()) {
            return [];
        }

        $attributes = $attribute !== null ? [$attribute] : ['head_id', 'deputy_head_id'];
        $occupied = [];

        foreach ($attributes as $column) {
            $query = static::find()
                ->select($column)
                ->andWhere(['not', [$column => null]]);

            if ($exceptDepartmentId !== null && $exceptDepartmentId > 0) {
                $query->andWhere(['not', ['id' => $exceptDepartmentId]]);
            }

            foreach ($query->column() as $employeeId) {
                $occupied[] = (int)$employeeId;
            }
        }

        return array_values(array_unique($occupied));
    }

    private function findOtherDepartmentByAttribute(string $attribute, int $employeeId): ?self
    {
        $query = static::find()->where([$attribute => $employeeId]);
        if (!$this->isNewRecord) {
            $query->andWhere(['not', ['id' => (int)$this->id]]);
        }

        return $query->one();
    }

    private function leadershipConflictMessage(string $role, self $department, string $template): string
    {
        $departmentName = trim((string)$department->name);
        if ($departmentName !== '') {
            return sprintf($template, $departmentName);
        }

        return $role === 'head'
            ? 'This employee already leads another department.'
            : 'This employee is already deputy head of another department.';
    }

    public function getHead()
    {
        return $this->hasOne(Employee::class, ['id' => 'head_id']);
    }

    public function getDeputyHead()
    {
        return $this->hasOne(Employee::class, ['id' => 'deputy_head_id']);
    }

    public function getGrades()
    {
        return $this->hasMany(SchoolGrade::class, ['school_department_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);
    }

    public function getSubjects()
    {
        return $this->hasMany(SchoolSubject::class, ['school_department_id' => 'id'])
            ->orderBy(['name' => SORT_ASC]);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        if ($insert && !$this->created_at) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
