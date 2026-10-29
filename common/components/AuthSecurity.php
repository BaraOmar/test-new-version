<?php

namespace common\components;

use Yii;
use yii\base\Component;

class AuthSecurity extends Component
{
    public $passwordCost = 12;
    public $maxLoginAttempts = 5;
    public $lockoutMinutes = 15;

    public function hashPassword($password)
    {
        return Yii::$app->security->generatePasswordHash((string)$password, $this->passwordCost);
    }

    public function validatePassword($password, $hash)
    {
        if (!$hash || strlen((string)$hash) < 40) {
            return false;
        }

        try {
            return Yii::$app->security->validatePassword((string)$password, (string)$hash);
        } catch (\Throwable $exception) {
            return password_verify((string)$password, (string)$hash);
        }
    }

    public function passwordNeedsRehash($hash)
    {
        return password_needs_rehash((string)$hash, PASSWORD_BCRYPT, ['cost' => $this->passwordCost]);
    }

    public function validatePasswordStrength($password, $required = true)
    {
        $password = (string)$password;
        $errors = [];
        if ($required && $password === '') {
            return ['Password is required.'];
        }
        if (!$required && $password === '') {
            return [];
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if (strlen($password) > 128) {
            $errors[] = 'Password must not exceed 128 characters.';
        }

        return $errors;
    }

    public function checkLoginLockout($identifier)
    {
        $this->ensureLoginAttemptsTable();
        $row = (new \yii\db\Query())
            ->select(['attempts', 'locked_until'])
            ->from('{{%login_attempts}}')
            ->where(['identifier' => $this->normalizeIdentifier($identifier)])
            ->one();

        if (!$row) {
            return null;
        }

        if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
            $minutes = (int)ceil((strtotime($row['locked_until']) - time()) / 60);
            return "Account temporarily locked. Try again in {$minutes} minutes.";
        }

        if ($row['locked_until'] && strtotime($row['locked_until']) <= time()) {
            Yii::$app->db->createCommand()->update('{{%login_attempts}}', [
                'attempts' => 0,
                'locked_until' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['identifier' => $this->normalizeIdentifier($identifier)])->execute();
        }

        return null;
    }

    public function recordFailedLogin($identifier)
    {
        $this->ensureLoginAttemptsTable();
        $identifier = $this->normalizeIdentifier($identifier);
        $row = (new \yii\db\Query())->from('{{%login_attempts}}')->where(['identifier' => $identifier])->one();
        $now = date('Y-m-d H:i:s');

        if (!$row) {
            Yii::$app->db->createCommand()->insert('{{%login_attempts}}', [
                'identifier' => $identifier,
                'attempts' => 1,
                'locked_until' => null,
                'updated_at' => $now,
            ])->execute();
            return;
        }

        $lockedUntil = $row['locked_until'] && strtotime($row['locked_until']) > time() ? $row['locked_until'] : null;
        $attempts = $lockedUntil ? (int)$row['attempts'] : (int)$row['attempts'] + 1;
        if (!$lockedUntil && $attempts >= $this->maxLoginAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', time() + ($this->lockoutMinutes * 60));
        }

        Yii::$app->db->createCommand()->update('{{%login_attempts}}', [
            'attempts' => $attempts,
            'locked_until' => $lockedUntil,
            'updated_at' => $now,
        ], ['identifier' => $identifier])->execute();
    }

    public function clearLoginAttempts($identifier)
    {
        $this->ensureLoginAttemptsTable();
        Yii::$app->db->createCommand()->delete('{{%login_attempts}}', [
            'identifier' => $this->normalizeIdentifier($identifier),
        ])->execute();
    }

    private function ensureLoginAttemptsTable()
    {
        if (Yii::$app->db->schema->getTableSchema('{{%login_attempts}}', true) !== null) {
            return;
        }

        Yii::$app->db->createCommand()->createTable('{{%login_attempts}}', [
            'identifier' => 'varchar(255) NOT NULL PRIMARY KEY',
            'attempts' => 'integer NOT NULL DEFAULT 0',
            'locked_until' => 'datetime NULL',
            'updated_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
        ])->execute();
    }

    private function normalizeIdentifier($identifier)
    {
        return mb_strtolower(trim((string)$identifier));
    }
}
