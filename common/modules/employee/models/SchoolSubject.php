<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_department_id
 * @property string $name
 * @property string|null $code
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolSubject extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_subject}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['school_department_id', 'name'], 'required'],
            [['school_department_id'], 'integer'],
            [['name', 'code'], 'trim'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'validateName'],
            [['code'], 'string', 'max' => 32],
            [['created_at', 'updated_at'], 'safe'],
            [['school_department_id'], 'exist', 'targetClass' => SchoolDepartment::class, 'targetAttribute' => ['school_department_id' => 'id']],
        ];
    }

    public function getDepartment()
    {
        return $this->hasOne(SchoolDepartment::class, ['id' => 'school_department_id']);
    }

    public function getSubjectGrades()
    {
        return $this->hasMany(SchoolSubjectGrade::class, ['school_subject_id' => 'id'])
            ->orderBy(['school_grade_id' => SORT_ASC]);
    }

    public function validateName(string $attribute): void
    {
        $name = trim((string)($this->$attribute ?? ''));
        if ($name === '' || !$this->school_department_id) {
            return;
        }

        $query = static::find()->where([
            'school_department_id' => (int)$this->school_department_id,
            'name' => $name,
        ]);

        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', (int)$this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'This subject name is already used in this department.');
        }
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
