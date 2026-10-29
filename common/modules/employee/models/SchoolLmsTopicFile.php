<?php

namespace common\modules\employee\models;

use common\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $topic_id
 * @property int $school_section_material_id
 * @property string $title
 * @property string $original_name
 * @property string $stored_name
 * @property string $storage_path
 * @property string $extension
 * @property string|null $mime_type
 * @property int $file_size
 * @property int $sequence
 * @property int|null $uploaded_by_user_id
 * @property string $created_at
 *
 * @property SchoolLmsCourseTopic $topic
 * @property SchoolSectionMaterial $course
 */
class SchoolLmsTopicFile extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%school_lms_topic_file}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['topic_id', 'school_section_material_id', 'title', 'original_name', 'stored_name', 'storage_path', 'extension'], 'required'],
            [['topic_id', 'school_section_material_id', 'file_size', 'sequence', 'uploaded_by_user_id'], 'integer'],
            [['title', 'original_name', 'stored_name'], 'string', 'max' => 255],
            [['storage_path'], 'string', 'max' => 500],
            [['extension'], 'string', 'max' => 16],
            [['mime_type'], 'string', 'max' => 150],
            [['file_size'], 'default', 'value' => 0],
            [['sequence'], 'default', 'value' => 10],
            [['topic_id'], 'exist', 'targetClass' => SchoolLmsCourseTopic::class, 'targetAttribute' => ['topic_id' => 'id']],
            [['school_section_material_id'], 'exist', 'targetClass' => SchoolSectionMaterial::class, 'targetAttribute' => ['school_section_material_id' => 'id']],
            [['uploaded_by_user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['uploaded_by_user_id' => 'id'], 'skipOnEmpty' => true],
            [['created_at'], 'safe'],
        ];
    }

    public function getTopic()
    {
        return $this->hasOne(SchoolLmsCourseTopic::class, ['id' => 'topic_id']);
    }

    public function getCourse()
    {
        return $this->hasOne(SchoolSectionMaterial::class, ['id' => 'school_section_material_id']);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && !$this->created_at) {
            $this->created_at = date('Y-m-d H:i:s');
        }

        return true;
    }

    /**
     * @return array{icon:string,bg:string,color:string,label:string}
     */
    public function displayMeta(): array
    {
        $ext = strtolower((string)$this->extension);

        if (in_array($ext, ['pdf'], true)) {
            return ['icon' => 'picture_as_pdf', 'bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'PDF'];
        }
        if (in_array($ext, ['doc', 'docx', 'txt', 'rtf'], true)) {
            return ['icon' => 'description', 'bg' => '#eff6ff', 'color' => '#2563eb', 'label' => 'Document'];
        }
        if (in_array($ext, ['ppt', 'pptx'], true)) {
            return ['icon' => 'slideshow', 'bg' => '#fff7ed', 'color' => '#ea580c', 'label' => 'Presentation'];
        }
        if (in_array($ext, ['xls', 'xlsx', 'csv', 'numbers'], true)) {
            return ['icon' => 'table_chart', 'bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'Spreadsheet'];
        }
        if (in_array($ext, ['mp4', 'mov', 'avi', 'wmv', 'webm', 'flv'], true)) {
            return ['icon' => 'movie', 'bg' => '#f5f3ff', 'color' => '#7c3aed', 'label' => 'Video'];
        }
        if (in_array($ext, ['mp3', 'wav', 'aac', 'wma'], true)) {
            return ['icon' => 'audio_file', 'bg' => '#fdf2f8', 'color' => '#db2777', 'label' => 'Audio'];
        }
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'tif', 'tiff'], true)) {
            return ['icon' => 'image', 'bg' => '#ecfeff', 'color' => '#0891b2', 'label' => 'Image'];
        }

        return ['icon' => 'draft', 'bg' => '#eff6ff', 'color' => '#2563eb', 'label' => 'File'];
    }

    public function sizeLabel(): string
    {
        $bytes = (int)$this->file_size;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return rtrim(rtrim(number_format($bytes / 1024, 1, '.', ''), '0'), '.') . ' KB';
        }

        return rtrim(rtrim(number_format($bytes / 1048576, 1, '.', ''), '0'), '.') . ' MB';
    }
}
