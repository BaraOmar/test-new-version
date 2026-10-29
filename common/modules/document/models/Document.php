<?php

namespace common\modules\document\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $original_name
 * @property string $stored_name
 * @property string $storage_path
 * @property string $extension
 * @property string $mime_type
 * @property int $file_size
 * @property string $sha256_hash
 * @property int|null $uploaded_by
 * @property string|null $created_at
 */
class Document extends ActiveRecord
{
    public const MAX_UPLOAD_SIZE = 104857600;

    /** @var UploadedFile|null */
    public $uploadFile;

    public static function tableName()
    {
        return '{{%documents}}';
    }

    public function rules()
    {
        return [
            [['title'], 'required'],
            [['description'], 'string'],
            [['uploaded_by', 'file_size'], 'integer'],
            [['created_at'], 'safe'],
            [['title', 'original_name', 'stored_name'], 'string', 'max' => 255],
            [['storage_path'], 'string', 'max' => 500],
            [['extension'], 'string', 'max' => 16],
            [['mime_type'], 'string', 'max' => 150],
            [['sha256_hash'], 'string', 'max' => 64],
            [['uploadFile'], 'file',
                'skipOnEmpty' => false,
                'maxSize' => self::MAX_UPLOAD_SIZE,
                'extensions' => array_keys(self::allowedMimeTypes()),
                'checkExtensionByMimeType' => false,
            ],
            [['description', 'uploaded_by'], 'default', 'value' => null],
        ];
    }

    public function attributeLabels()
    {
        return [
            'uploadFile' => 'Document File',
            'original_name' => 'File Name',
            'mime_type' => 'MIME Type',
            'file_size' => 'File Size',
            'sha256_hash' => 'SHA-256 Hash',
        ];
    }

    public static function allowedMimeTypes()
    {
        return [
            'mp4' => ['video/mp4'],
            'webm' => ['video/webm'],
            'ogg' => ['video/ogg', 'application/ogg'],
            'mov' => ['video/quicktime'],
            'avi' => ['video/x-msvideo'],
            'mkv' => ['video/x-matroska'],
            'pdf' => ['application/pdf'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'doc' => ['application/msword'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'bmp' => ['image/bmp', 'image/x-ms-bmp'],
        ];
    }

    public function uploadToSecureStorage($storageRoot)
    {
        if (!$this->uploadFile instanceof UploadedFile) {
            return false;
        }

        $extension = strtolower($this->uploadFile->extension);
        if (!isset(self::allowedMimeTypes()[$extension])) {
            $this->addError('uploadFile', 'File type is not allowed.');
            return false;
        }

        $detectedMime = $this->detectMimeType($this->uploadFile->tempName);
        if (!in_array($detectedMime, self::allowedMimeTypes()[$extension], true)) {
            $this->addError('uploadFile', 'File content does not match the selected extension.');
            return false;
        }

        $directory = rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            $this->addError('uploadFile', 'Secure storage directory could not be created.');
            return false;
        }

        $storedName = Yii::$app->security->generateRandomString(32) . '.' . $extension;
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $storedName;
        if (!$this->uploadFile->saveAs($absolutePath)) {
            $this->addError('uploadFile', 'Failed to save uploaded file.');
            return false;
        }

        $realStorage = realpath($storageRoot);
        $realFile = realpath($absolutePath);
        if ($realStorage === false || $realFile === false || strpos($realFile, $realStorage . DIRECTORY_SEPARATOR) !== 0) {
            @unlink($absolutePath);
            $this->addError('uploadFile', 'Invalid storage path.');
            return false;
        }

        $this->original_name = substr($this->uploadFile->name, 0, 255);
        $this->stored_name = $storedName;
        $this->storage_path = str_replace($realStorage . DIRECTORY_SEPARATOR, '', $realFile);
        $this->extension = $extension;
        $this->mime_type = $detectedMime;
        $this->file_size = (int)$this->uploadFile->size;
        $this->sha256_hash = hash_file('sha256', $realFile);
        $this->uploaded_by = Yii::$app->user->isGuest ? null : (int)Yii::$app->user->id;

        return true;
    }

    public function getAbsolutePath($storageRoot)
    {
        $base = realpath($storageRoot);
        $path = realpath(rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->storage_path);
        if ($base === false || $path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }

        return $path;
    }

    public function getFormattedSize()
    {
        $size = (int)$this->file_size;
        if ($size >= 1048576) {
            return round($size / 1048576, 2) . ' MB';
        }
        if ($size >= 1024) {
            return round($size / 1024, 2) . ' KB';
        }

        return $size . ' B';
    }

    private function detectMimeType($path)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime ?: 'application/octet-stream';
    }
}
