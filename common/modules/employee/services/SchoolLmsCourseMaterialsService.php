<?php

namespace common\modules\employee\services;

use common\models\User;
use common\modules\employee\models\SchoolLmsCourseTopic;
use common\modules\employee\models\SchoolLmsTopicFile;
use common\modules\employee\models\SchoolSectionMaterial;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class SchoolLmsCourseMaterialsService
{
    public const MAX_UPLOAD_SIZE = 524288000;
    public const MAX_FILES_PER_UPLOAD = 10;

    public static function isEnabled(): bool
    {
        return SchoolLmsCourseTopic::isEnabled();
    }

    /**
     * @return string[]
     */
    public static function allowedExtensions(): array
    {
        return [
            'pdf', 'doc', 'docx', 'txt', 'rtf',
            'ppt', 'pptx',
            'xls', 'xlsx', 'csv', 'numbers',
            'mp4', 'mov', 'avi', 'wmv', 'webm', 'flv',
            'mp3', 'wav', 'aac', 'wma',
            'jpg', 'jpeg', 'png', 'gif', 'svg', 'tif', 'tiff',
        ];
    }

    public static function acceptAttribute(): string
    {
        return implode(',', array_map(static fn(string $ext): string => '.' . $ext, self::allowedExtensions()));
    }

    public static function effectiveMaxBytes(): int
    {
        $limits = [
            self::bytesFromIni((string)ini_get('upload_max_filesize')),
            self::bytesFromIni((string)ini_get('post_max_size')),
            self::MAX_UPLOAD_SIZE,
        ];
        $limits = array_values(array_filter($limits, static fn(int $value): bool => $value > 0));

        return $limits === [] ? self::MAX_UPLOAD_SIZE : min($limits);
    }

    public static function maxSizeLabel(): string
    {
        return '500 MB';
    }

    private static function bytesFromIni(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float)$value;
        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * @return SchoolLmsCourseTopic[]
     */
    public function listTopics(SchoolSectionMaterial $course): array
    {
        if (!self::isEnabled()) {
            return [];
        }

        return SchoolLmsCourseTopic::find()
            ->with(['files'])
            ->where(['school_section_material_id' => (int)$course->id])
            ->orderBy(['sequence' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    public function ensureDefaultTopic(SchoolSectionMaterial $course, User $user): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $exists = SchoolLmsCourseTopic::find()
            ->where(['school_section_material_id' => (int)$course->id])
            ->exists();
        if ($exists) {
            return;
        }

        $this->createTopic($course, 'General', $user);
    }

    /**
     * @return array{success:bool,message:string,topic?:SchoolLmsCourseTopic}
     */
    public function createTopic(SchoolSectionMaterial $course, string $name, User $user): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Learning materials storage is not ready. Run: php yii migrate/up'];
        }

        $name = trim($name);
        if ($name === '') {
            return ['success' => false, 'message' => 'Enter a topic name.'];
        }

        $maxSequence = (int)SchoolLmsCourseTopic::find()
            ->where(['school_section_material_id' => (int)$course->id])
            ->max('sequence');

        $topic = new SchoolLmsCourseTopic();
        $topic->school_section_material_id = (int)$course->id;
        $topic->name = $name;
        $topic->sequence = $maxSequence + 10;
        $topic->created_by_user_id = (int)$user->id;
        if (!$topic->save()) {
            return ['success' => false, 'message' => $this->firstError($topic->getErrors(), 'Unable to create the topic.')];
        }

        return ['success' => true, 'message' => 'Topic created.', 'topic' => $topic];
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function renameTopic(SchoolLmsCourseTopic $topic, string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['success' => false, 'message' => 'Enter a topic name.'];
        }

        $topic->name = $name;
        if (!$topic->save(true, ['name', 'updated_at'])) {
            return ['success' => false, 'message' => $this->firstError($topic->getErrors(), 'Unable to rename the topic.')];
        }

        return ['success' => true, 'message' => 'Topic renamed.'];
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function deleteTopic(SchoolLmsCourseTopic $topic): array
    {
        foreach ($topic->files as $file) {
            $this->deleteStoredFile($file);
        }

        if (!$topic->delete()) {
            return ['success' => false, 'message' => 'Unable to delete the topic.'];
        }

        return ['success' => true, 'message' => 'Topic deleted.'];
    }

    /**
     * @param UploadedFile[] $uploads
     * @return array{success:bool,message:string}
     */
    public function uploadFiles(SchoolLmsCourseTopic $topic, array $uploads, User $user): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Learning materials storage is not ready. Run: php yii migrate/up'];
        }

        $uploads = array_values(array_filter(
            $uploads,
            static fn($file): bool => $file instanceof UploadedFile && (int)$file->error !== UPLOAD_ERR_NO_FILE
        ));
        if ($uploads === []) {
            return ['success' => false, 'message' => 'Choose one or more files to upload.'];
        }
        if (count($uploads) > self::MAX_FILES_PER_UPLOAD) {
            return ['success' => false, 'message' => 'You can upload up to ' . self::MAX_FILES_PER_UPLOAD . ' files at a time.'];
        }

        $saved = 0;
        $errors = [];
        $sequence = (int)SchoolLmsTopicFile::find()->where(['topic_id' => (int)$topic->id])->max('sequence');

        foreach ($uploads as $upload) {
            $result = $this->storeUpload($topic, $upload, $user, $sequence + 10);
            if ($result['success']) {
                $saved++;
                $sequence += 10;
            } else {
                $errors[] = $upload->name . ': ' . $result['message'];
            }
        }

        if ($saved === 0) {
            return ['success' => false, 'message' => implode(' ', $errors) ?: 'No files were uploaded.'];
        }

        $message = $saved === 1 ? 'File uploaded.' : $saved . ' files uploaded.';
        if ($errors !== []) {
            $message .= ' Some files were skipped. ' . implode(' ', $errors);
        }

        return ['success' => true, 'message' => $message];
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function deleteFile(SchoolLmsTopicFile $file): array
    {
        $this->deleteStoredFile($file);
        if (!$file->delete()) {
            return ['success' => false, 'message' => 'Unable to delete the file.'];
        }

        return ['success' => true, 'message' => 'File deleted.'];
    }

    public function download(SchoolLmsTopicFile $file): Response
    {
        $absolute = $this->absolutePath($file);
        if ($absolute === null || !is_file($absolute)) {
            throw new NotFoundHttpException('The file is no longer available.');
        }

        return Yii::$app->response->sendFile($absolute, $file->original_name, [
            'inline' => false,
        ]);
    }

    /**
     * @return array{success:bool,message:string}
     */
    private function storeUpload(SchoolLmsCourseTopic $topic, UploadedFile $upload, User $user, int $sequence): array
    {
        if ((int)$upload->error !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => $this->uploadErrorMessage((int)$upload->error)];
        }
        if ((int)$upload->size > self::MAX_UPLOAD_SIZE) {
            return ['success' => false, 'message' => 'A file cannot exceed 500 MB.'];
        }

        $extension = strtolower((string)$upload->extension);
        if ($extension === 'tif') {
            $extension = 'tiff';
        }
        if (!in_array($extension, self::allowedExtensions(), true)) {
            return ['success' => false, 'message' => 'This file type is not allowed.'];
        }

        $root = $this->storageRoot();
        if ($root === null) {
            return ['success' => false, 'message' => 'Storage directory could not be created.'];
        }

        $directory = $root . DIRECTORY_SEPARATOR . (int)$topic->school_section_material_id . DIRECTORY_SEPARATOR . (int)$topic->id;
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            return ['success' => false, 'message' => 'Storage directory could not be created.'];
        }

        $storedName = Yii::$app->security->generateRandomString(32) . '.' . $extension;
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $storedName;
        if (!$upload->saveAs($absolutePath)) {
            return ['success' => false, 'message' => 'Failed to save the uploaded file.'];
        }

        $realRoot = realpath($root);
        $realFile = realpath($absolutePath);
        if ($realRoot === false || $realFile === false || !str_starts_with($realFile, $realRoot . DIRECTORY_SEPARATOR)) {
            @unlink($absolutePath);
            return ['success' => false, 'message' => 'Invalid storage path.'];
        }

        $originalName = substr((string)$upload->name, 0, 255) ?: ('file.' . $extension);
        $title = pathinfo($originalName, PATHINFO_FILENAME);
        $title = trim((string)$title);
        if ($title === '') {
            $title = $originalName;
        }

        $file = new SchoolLmsTopicFile();
        $file->topic_id = (int)$topic->id;
        $file->school_section_material_id = (int)$topic->school_section_material_id;
        $file->title = substr($title, 0, 255);
        $file->original_name = $originalName;
        $file->stored_name = $storedName;
        $file->storage_path = ltrim(str_replace($realRoot, '', $realFile), DIRECTORY_SEPARATOR);
        $file->extension = $extension;
        $file->mime_type = $upload->type !== '' ? substr((string)$upload->type, 0, 150) : null;
        $file->file_size = (int)$upload->size;
        $file->sequence = $sequence;
        $file->uploaded_by_user_id = (int)$user->id;
        if (!$file->save()) {
            @unlink($absolutePath);
            return ['success' => false, 'message' => $this->firstError($file->getErrors(), 'Unable to save the file record.')];
        }

        return ['success' => true, 'message' => 'File uploaded.'];
    }

    private function deleteStoredFile(SchoolLmsTopicFile $file): void
    {
        $absolute = $this->absolutePath($file);
        if ($absolute !== null && is_file($absolute)) {
            @unlink($absolute);
        }
    }

    private function absolutePath(SchoolLmsTopicFile $file): ?string
    {
        $root = $this->storageRoot();
        if ($root === null || trim((string)$file->storage_path) === '') {
            return null;
        }

        $realRoot = realpath($root);
        if ($realRoot === false) {
            return null;
        }

        $candidate = $realRoot . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$file->storage_path), DIRECTORY_SEPARATOR);
        $realFile = realpath($candidate);
        if ($realFile === false || !str_starts_with($realFile, $realRoot . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $realFile;
    }

    private function storageRoot(): ?string
    {
        $root = Yii::getAlias('@frontend/runtime/lms-materials', false);
        if (!is_string($root) || $root === '') {
            return null;
        }
        if (!is_dir($root) && !mkdir($root, 0770, true) && !is_dir($root)) {
            return null;
        }

        return $root;
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A file cannot exceed 500 MB.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Try again.',
            UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
            default => 'The file could not be uploaded.',
        };
    }

    /**
     * @param array<string, string[]> $errors
     */
    private function firstError(array $errors, string $fallback): string
    {
        foreach ($errors as $messages) {
            if (isset($messages[0]) && is_string($messages[0]) && $messages[0] !== '') {
                return $messages[0];
            }
        }

        return $fallback;
    }
}
