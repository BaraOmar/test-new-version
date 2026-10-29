<?php

namespace common\modules\transport\models;

use common\modules\employee\models\Employee;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $plate_number
 * @property int $capacity
 * @property int|null $transport_area_id
 * @property int|null $driver_employee_id
 * @property int|null $supervisor_employee_id
 * @property string|null $notes
 * @property bool $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TransportArea|null $area
 * @property Employee|null $driver
 * @property Employee|null $supervisor
 * @property TransportBusAssignment[] $assignments
 */
class TransportBus extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%transport_bus}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules(): array
    {
        return [
            [['plate_number'], 'required'],
            [['plate_number'], 'string', 'max' => 64],
            [['plate_number'], 'unique'],
            [['capacity'], 'integer', 'min' => 1, 'max' => 200],
            [['capacity'], 'default', 'value' => 30],
            [['transport_area_id', 'driver_employee_id', 'supervisor_employee_id'], 'integer'],
            [['notes'], 'string'],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
            [['plate_number'], 'trim'],
            [['transport_area_id'], 'exist', 'skipOnError' => true, 'targetClass' => TransportArea::class, 'targetAttribute' => ['transport_area_id' => 'id']],
            [['driver_employee_id'], 'exist', 'skipOnError' => true, 'targetClass' => Employee::class, 'targetAttribute' => ['driver_employee_id' => 'id']],
            [['supervisor_employee_id'], 'exist', 'skipOnError' => true, 'targetClass' => Employee::class, 'targetAttribute' => ['supervisor_employee_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'plate_number' => 'Plate Number',
            'capacity' => 'Capacity',
            'transport_area_id' => 'Area',
            'driver_employee_id' => 'Driver',
            'supervisor_employee_id' => 'Supervisor',
            'notes' => 'Notes',
            'is_active' => 'Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getArea()
    {
        return $this->hasOne(TransportArea::class, ['id' => 'transport_area_id']);
    }

    public function getDriver()
    {
        return $this->hasOne(Employee::class, ['id' => 'driver_employee_id']);
    }

    public function getSupervisor()
    {
        return $this->hasOne(Employee::class, ['id' => 'supervisor_employee_id']);
    }

    public function getAssignments()
    {
        return $this->hasMany(TransportBusAssignment::class, ['transport_bus_id' => 'id']);
    }

    public function getAssignedStudentCount(): int
    {
        return (int)$this->getAssignments()->count();
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
