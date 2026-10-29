<?php

namespace common\modules\employee\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property int|null $manager_id
 * @property string|null $status
 */
class Department extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%departments}}';
    }

    public function rules()
    {
        $rules = [
            [['name'], 'required'],
            [['name'], 'trim'],
            [['name'], 'string', 'max' => 255],
            [
                ['name'],
                'unique',
                'message' => 'A department with this name already exists.',
            ],
        ];

        if (self::hasColumn('manager_id')) {
            $rules[] = [[
                'manager_id',
            ], 'filter', 'filter' => static function ($value) {
                if ($value === '' || $value === null) {
                    return null;
                }

                return (int)$value;
            }];
            $rules[] = [['manager_id'], 'integer', 'skipOnEmpty' => true];
            $rules[] = [[
                'manager_id',
            ], 'exist', 'targetClass' => Employee::class, 'targetAttribute' => ['manager_id' => 'id'], 'skipOnEmpty' => true];
        }

        if (self::hasColumn('status')) {
            $rules[] = [['status'], 'required'];
            $rules[] = [['status'], 'in', 'range' => ['active', 'inactive']];
        }

        return $rules;
    }

    public function getEmployees()
    {
        return $this->hasMany(Employee::class, ['department_id' => 'id']);
    }

    public function getManager()
    {
        return $this->hasOne(Employee::class, ['id' => 'manager_id']);
    }

    public static function hasColumn($column): bool
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName());
        return $schema !== null && $schema->getColumn($column) !== null;
    }
}
