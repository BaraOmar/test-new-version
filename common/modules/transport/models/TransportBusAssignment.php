<?php

namespace common\modules\transport\models;

use common\modules\employee\models\SchoolStudent;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $transport_bus_id
 * @property int $school_student_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TransportBus $bus
 * @property SchoolStudent $student
 */
class TransportBusAssignment extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%transport_bus_assignment}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules(): array
    {
        return [
            [['transport_bus_id', 'school_student_id'], 'required'],
            [['transport_bus_id', 'school_student_id'], 'integer'],
            [['school_student_id'], 'unique'],
            [['transport_bus_id'], 'exist', 'targetClass' => TransportBus::class, 'targetAttribute' => ['transport_bus_id' => 'id']],
            [['school_student_id'], 'exist', 'targetClass' => SchoolStudent::class, 'targetAttribute' => ['school_student_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'transport_bus_id' => 'Bus',
            'school_student_id' => 'Student',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getBus()
    {
        return $this->hasOne(TransportBus::class, ['id' => 'transport_bus_id']);
    }

    public function getStudent()
    {
        return $this->hasOne(SchoolStudent::class, ['id' => 'school_student_id']);
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
