<?php

namespace common\modules\recruitment\models;

use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * @property int $user_id
 * @property int $role_id
 * @property string|null $username
 * @property string $full_name
 * @property string $email
 * @property string $password_hash
 * @property string|null $phone
 * @property string|null $headline
 * @property string|null $bio
 * @property string|null $resume_path
 * @property string|null $resume_original_name
 * @property string|null $resume_uploaded_at
 */
class Applicant extends ActiveRecord
{
    public const ROLE_RECRUITER = 1;
    public const ROLE_APPLICANT = 2;

    public $password;
    public $resumeFile;

    public static function tableName()
    {
        return '{{%users}}';
    }

    public static function primaryKey()
    {
        return ['user_id'];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['login'] = ['email', 'password'];
        $scenarios['register'] = ['full_name', 'email', 'phone', 'password'];
        $scenarios['profile'] = [
            'full_name', 'email', 'phone', 'headline', 'bio', 'resumeFile',
        ];

        return $scenarios;
    }

    public function rules()
    {
        return [
            [['email', 'password'], 'required', 'on' => 'login'],
            [['full_name', 'email', 'phone', 'password'], 'required', 'on' => 'register'],
            [['full_name', 'email'], 'required', 'on' => 'profile'],
            [['role_id'], 'default', 'value' => self::ROLE_APPLICANT, 'on' => 'register'],
            [['role_id', 'full_name', 'email'], 'required', 'on' => 'default'],
            [['password'], 'required', 'on' => 'register'],
            [['role_id'], 'integer'],
            [['bio'], 'string'],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['phone'], 'trim'],
            [['phone'], 'unique', 'targetAttribute' => ['phone', 'role_id'], 'filter' => function ($query) {
                $query->andWhere(['role_id' => self::ROLE_APPLICANT]);
                if (!$this->isNewRecord) {
                    $query->andWhere(['<>', 'user_id', $this->user_id]);
                }
            }, 'message' => 'This phone number is already registered.', 'on' => ['register', 'profile'], 'skipOnEmpty' => true],
            [['full_name', 'email', 'username', 'headline', 'resume_original_name'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 50],
            [['password'], 'string', 'min' => 8],
            [['resume_path'], 'string', 'max' => 500],
            [['resume_uploaded_at'], 'safe'],
            [['resumeFile'], 'file', 'skipOnEmpty' => true, 'extensions' => ['pdf', 'doc', 'docx', 'png'], 'maxSize' => 5 * 1024 * 1024],
            [['username', 'phone', 'headline', 'bio', 'resume_path', 'resume_original_name', 'resume_uploaded_at'], 'default', 'value' => null],
        ];
    }

    public function attributeLabels()
    {
        return [
            'full_name' => 'Full Name',
            'phone' => 'Phone Number',
            'role_id' => 'Role',
            'resumeFile' => 'Resume',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->password) {
            $this->password_hash = password_hash($this->password, PASSWORD_DEFAULT);
        }

        if ($this->username === null || $this->username === '') {
            $this->username = $this->email;
        }

        if ($this->phone !== null && $this->phone !== '') {
            $this->phone = preg_replace('/\s+/', '', trim((string)$this->phone));
        }

        return true;
    }

    public function validatePassword($password)
    {
        return password_verify($password, (string)$this->password_hash);
    }

    public function uploadResume($basePath)
    {
        if (!$this->resumeFile instanceof UploadedFile) {
            return true;
        }

        $directory = rtrim($basePath, '/') . '/uploads/resumes';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $fileName = 'resume_' . $this->user_id . '_' . time() . '.' . $this->resumeFile->extension;
        if (!$this->resumeFile->saveAs($directory . '/' . $fileName)) {
            return false;
        }

        $this->resume_path = 'uploads/resumes/' . $fileName;
        $this->resume_original_name = $this->resumeFile->name;
        $this->resume_uploaded_at = date('Y-m-d H:i:s');

        return true;
    }

    public function getApplications()
    {
        return $this->hasMany(Application::class, ['applicant_id' => 'user_id']);
    }
}
