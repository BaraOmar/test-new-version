<?php

namespace common\modules\employee\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $employee_id
 * @property string|null $title
 * @property string|null $old_value
 * @property string $new_value
 * @property string $urgency
 * @property string $status
 */
class EmployeeChangeRequest extends ActiveRecord
{
    public const URGENCY_NORMAL = 'Normal';
    public const URGENCY_URGENT = 'Urgent';
    public const STATUS_PENDING = 'Pending';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';

    public static function tableName()
    {
        return '{{%employee_change_requests}}';
    }

    public function rules()
    {
        $required = ['employee_id', 'new_value'];
        if (self::hasColumn('title')) {
            $required[] = 'title';
        }

        $rules = [
            [$required, 'required'],
            [['employee_id'], 'integer'],
            [['old_value', 'new_value'], 'string', 'max' => 5000],
            ['urgency', 'in', 'range' => [self::URGENCY_NORMAL, self::URGENCY_URGENT]],
            ['urgency', 'default', 'value' => self::URGENCY_NORMAL],
            ['status', 'default', 'value' => self::STATUS_PENDING],
            ['status', 'string', 'max' => 50],
            ['employee_id', 'exist', 'targetClass' => Employee::class, 'targetAttribute' => ['employee_id' => 'id']],
        ];

        if (self::hasColumn('title')) {
            $rules[] = [['title'], 'trim'];
            $rules[] = [['title'], 'string', 'max' => 255];
        }

        return $rules;
    }

    public function attributeLabels()
    {
        return [
            'title' => 'Request title',
            'old_value' => 'Current content',
            'new_value' => 'New content',
            'urgency' => 'Priority',
        ];
    }

    public static function hasColumn(string $column): bool
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName(), true);

        return $schema !== null && isset($schema->columns[$column]);
    }

    public function getDisplayTitle(): string
    {
        if ($this->hasAttribute('title')) {
            $title = trim((string)$this->getAttribute('title'));
            if ($title !== '') {
                return $title;
            }
        }

        $preview = trim((string)$this->new_value);
        if ($preview === '') {
            return 'Request #' . (int)$this->id;
        }

        if (strlen($preview) > 72) {
            return substr($preview, 0, 72) . '…';
        }

        return $preview;
    }

    public function getEmployee()
    {
        return $this->hasOne(Employee::class, ['id' => 'employee_id']);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }

        $this->updated_at = date('Y-m-d H:i:s');

        return true;
    }

    /**
     * @return array{class:string,label:string}
     */
    public function getStatusBadge(): array
    {
        return match (strtolower(trim((string)$this->status))) {
            'approved' => [
                'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                'label' => self::STATUS_APPROVED,
            ],
            'rejected' => [
                'class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                'label' => self::STATUS_REJECTED,
            ],
            default => [
                'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                'label' => self::STATUS_PENDING,
            ],
        };
    }

    public function isPending(): bool
    {
        $status = strtolower(trim((string)$this->status));

        return $status === '' || $status === strtolower(self::STATUS_PENDING);
    }
}
