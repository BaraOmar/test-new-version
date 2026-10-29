<?php

namespace common\modules\employee\models;

use common\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_student_id
 * @property int $school_section_material_id
 * @property float|null $mark_first
 * @property float|null $mark_second
 * @property float|null $mark_third
 * @property float|null $mark_final
 * @property bool $mark_components_complete
 * @property float|null $total
 * @property float $maximum_score
 * @property string|null $status
 * @property int|null $updated_by_user_id
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolStudentSectionMark extends ActiveRecord
{
    public const STATUS_PASS = 'pass';
    public const STATUS_FAILED = 'failed';
    public const STATUS_INCOMPLETE = 'incomplete';

    public const MARK_FIELDS = [
        'first' => 'mark_first',
        'second' => 'mark_second',
        'third' => 'mark_third',
        'final' => 'mark_final',
    ];

    public const TERM_MAX_FIELDS = [
        'first' => 'max_first',
        'second' => 'max_second',
        'third' => 'max_third',
        'final' => 'max_final',
    ];

    public static function tableName()
    {
        return '{{%school_student_section_mark}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PASS => 'Pass',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_INCOMPLETE => 'Incomplete',
        ];
    }

    public static function hasColumn(string $column): bool
    {
        $schema = \Yii::$app->db->schema->getTableSchema(static::tableName(), true);

        return $schema !== null && isset($schema->columns[$column]);
    }

    public function rules()
    {
        return [
            [['school_student_id', 'school_section_material_id'], 'required'],
            [['school_student_id', 'school_section_material_id', 'updated_by_user_id'], 'integer'],
            [['mark_first', 'mark_second', 'mark_third', 'mark_final', 'total', 'maximum_score'], 'number', 'min' => 0],
            [['mark_components_complete'], 'boolean'],
            [['status'], 'in', 'range' => array_keys(self::statusOptions()), 'skipOnEmpty' => true],
            [['created_at', 'updated_at'], 'safe'],
            [['school_student_id'], 'exist', 'targetClass' => SchoolStudent::class, 'targetAttribute' => ['school_student_id' => 'id']],
            [['school_section_material_id'], 'exist', 'targetClass' => SchoolSectionMaterial::class, 'targetAttribute' => ['school_section_material_id' => 'id']],
            [['updated_by_user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['updated_by_user_id' => 'id'], 'skipOnEmpty' => true],
        ];
    }

    public function getStudent()
    {
        return $this->hasOne(SchoolStudent::class, ['id' => 'school_student_id']);
    }

    public function getMaterial()
    {
        return $this->hasOne(SchoolSectionMaterial::class, ['id' => 'school_section_material_id']);
    }

    public function getUpdatedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by_user_id']);
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
