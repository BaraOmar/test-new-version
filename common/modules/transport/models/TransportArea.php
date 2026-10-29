<?php

namespace common\modules\transport\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float|null $price
 * @property bool $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TransportBus[] $buses
 */
class TransportArea extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%transport_area}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['price'], 'filter', 'filter' => static function ($value) {
                return $value === '' || $value === null ? null : $value;
            }],
            [['price'], 'number', 'min' => 0, 'skipOnEmpty' => true],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
            [['name'], 'trim'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Area Name',
            'description' => 'Description',
            'price' => 'Price',
            'is_active' => 'Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getBuses()
    {
        return $this->hasMany(TransportBus::class, ['transport_area_id' => 'id']);
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
