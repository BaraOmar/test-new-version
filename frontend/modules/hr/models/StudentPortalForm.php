<?php

namespace frontend\modules\hr\models;

use common\models\User;
use common\modules\employee\models\SchoolStudent;
use yii\base\Model;

class StudentPortalForm extends Model
{
    public ?int $existingUserId = null;
    public string $portal_email = '';
    public string $portal_password = '';
    public bool $isEdit = false;

    public static function forStudent(SchoolStudent $student, bool $isEdit): self
    {
        $form = new self(['isEdit' => $isEdit]);

        if (!$isEdit || !SchoolStudent::hasColumn('user_id') || (int)$student->user_id <= 0) {
            return $form;
        }

        $user = $student->user ?? User::findOne((int)$student->user_id);
        if ($user !== null) {
            $form->portal_email = trim((string)$user->email);
            $form->existingUserId = (int)$user->id;
        }

        if (SchoolStudent::hasColumn('login_password_plain')) {
            $form->portal_password = trim((string)($student->login_password_plain ?? ''));
        }

        return $form;
    }

    public function rules(): array
    {
        return [
            [['portal_email'], 'required'],
            [['portal_email'], 'trim'],
            [['portal_email'], 'email'],
            [['portal_email'], 'string', 'max' => 255],
            [['portal_email'], 'validateUniquePortalEmail'],
            [['portal_password'], 'string', 'min' => 8, 'skipOnEmpty' => true],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'portal_email' => 'Portal Email',
            'portal_password' => 'Portal Password',
        ];
    }

    public function validateUniquePortalEmail(string $attribute): void
    {
        $email = trim((string)$this->$attribute);
        if ($email === '') {
            return;
        }

        $existing = User::findByEmail($email);
        if ($existing !== null && (int)$existing->id !== (int)$this->existingUserId) {
            $this->addError($attribute, 'This email is already registered for a portal account.');
        }
    }

    public function hasLinkedAccount(): bool
    {
        return (int)$this->existingUserId > 0;
    }
}
