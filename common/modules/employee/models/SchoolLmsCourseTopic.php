<?php

namespace common\modules\employee\models;

use common\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_section_material_id
 * @property string $name
 * @property int $sequence
 * @property int|null $created_by_user_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property SchoolSectionMaterial $course
 * @property SchoolLmsTopicFile[] $files
 */
class SchoolLmsCourseTopic extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_lms_course_topic}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null
            && SchoolLmsTopicFile::isEnabled();
    }

    public function rules()
    {
        return [
            [['school_section_material_id', 'name'], 'required'],
            [['school_section_material_id', 'sequence', 'created_by_user_id'], 'integer'],
            [['name'], 'trim'],
            [['name'], 'string', 'max' => 255],
            [['sequence'], 'default', 'value' => 10],
            [['school_section_material_id'], 'exist', 'targetClass' => SchoolSectionMaterial::class, 'targetAttribute' => ['school_section_material_id' => 'id']],
            [['created_by_user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['created_by_user_id' => 'id'], 'skipOnEmpty' => true],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function getCourse()
    {
        return $this->hasOne(SchoolSectionMaterial::class, ['id' => 'school_section_material_id']);
    }

    public function getFiles()
    {
        return $this->hasMany(SchoolLmsTopicFile::class, ['topic_id' => 'id'])
            ->orderBy(['sequence' => SORT_ASC, 'id' => SORT_ASC]);
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
