<?php

namespace common\modules\finance\services;

use common\models\User;
use common\modules\finance\models\FinanceAccountParentChild;
use common\modules\finance\models\FinanceAccountParentProfile;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Transaction;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;

class FinanceParentProfileService
{
    public static function isEnabled(): bool
    {
        return FinanceAccountParentProfile::isEnabled();
    }

    public function buildIndexDataProvider(string $search = ''): ActiveDataProvider
    {
        $query = FinanceAccountParentProfile::find()
            ->alias('profile')
            ->with(['children', 'account', 'user'])
            ->orderBy([
                'profile.family_name' => SORT_ASC,
                'profile.first_name' => SORT_ASC,
            ]);

        $search = trim($search);
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'profile.first_name', $search],
                ['like', 'profile.family_name', $search],
                ['like', 'profile.phone', $search],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => false,
        ]);
    }

    public function findProfile(int $id): FinanceAccountParentProfile
    {
        $profile = FinanceAccountParentProfile::find()
            ->with(['children', 'account', 'user'])
            ->where(['id' => $id])
            ->one();

        if (!$profile instanceof FinanceAccountParentProfile) {
            throw new NotFoundHttpException('Parent profile not found.');
        }

        return $profile;
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function deleteProfile(int $id, string $confirmation): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Parent directory is not available. Run migrations first.'];
        }

        if (strtolower(trim($confirmation)) !== 'delete') {
            return ['success' => false, 'message' => 'Type delete to confirm removal.'];
        }

        $profile = $this->findProfile($id);
        $userId = (int)($profile->user_id ?? 0);
        $transaction = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);
        try {
            $this->removeProfileFiles($profile);
            if ($profile->delete() === false) {
                $transaction->rollBack();

                return ['success' => false, 'message' => 'Unable to delete parent.'];
            }
            if ($userId > 0) {
                $user = User::findOne($userId);
                if ($user instanceof User) {
                    $user->delete();
                }
            }

            $transaction->commit();

            return ['success' => true, 'message' => 'Parent deleted successfully.'];
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            Yii::error($exception->getMessage(), __METHOD__);

            return ['success' => false, 'message' => 'Unable to delete parent.'];
        }
    }

    /**
     * @return array{success:bool,message:string,profile:FinanceAccountParentProfile|null}
     */
    public function createProfile(): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Parent directory is not available. Run migrations first.', 'profile' => null];
        }

        $profile = new FinanceAccountParentProfile();
        $profile->finance_account_id = null;

        return $this->persistProfile($profile, true);
    }

    /**
     * @return array{success:bool,message:string,profile:FinanceAccountParentProfile|null}
     */
    public function updateProfile(int $profileId): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Parent directory is not available. Run migrations first.', 'profile' => null];
        }

        return $this->persistProfile($this->findProfile($profileId), false);
    }

    /**
     * @return array{success:bool,message:string,profile:FinanceAccountParentProfile|null,credentials?:array{email:string,password:string}|null}
     */
    private function persistProfile(FinanceAccountParentProfile $profile, bool $isCreate): array
    {
        $request = Yii::$app->request;

        $profile->first_name = trim((string)$request->post('parent_first_name', ''));
        $profile->second_name = trim((string)$request->post('parent_second_name', '')) ?: null;
        $profile->third_name = trim((string)$request->post('parent_third_name', '')) ?: null;
        $profile->family_name = trim((string)$request->post('parent_family_name', ''));
        $profile->phone = trim((string)$request->post('parent_phone', ''));
        $profile->emergency_phone_2 = trim((string)$request->post('parent_emergency_phone_2', ''));
        $profile->email = trim((string)$request->post('parent_email', '')) ?: null;
        $kidsCount = (int)$request->post('parent_kids_count', $isCreate ? 0 : 1);
        if ($kidsCount < 1) {
            return ['success' => false, 'message' => 'Add at least one child before creating the parent.', 'profile' => null];
        }
        if ($kidsCount > 20) {
            return ['success' => false, 'message' => 'A parent can have at most 20 children.', 'profile' => null];
        }
        $profile->kids_count = $kidsCount;

        if ($profile->first_name === '' || $profile->family_name === '') {
            return ['success' => false, 'message' => 'First name and family name are required.', 'profile' => null];
        }

        if ($profile->phone === '' || $profile->emergency_phone_2 === '') {
            return ['success' => false, 'message' => 'Parent phone and emergency phone are required.', 'profile' => null];
        }

        if (!preg_match('/^\d+$/', $profile->phone) || !preg_match('/^\d+$/', $profile->emergency_phone_2)) {
            return ['success' => false, 'message' => 'Phone numbers must contain numbers only.', 'profile' => null];
        }

        $duplicatePhone = FinanceAccountParentProfile::find()
            ->where(['phone' => $profile->phone])
            ->andFilterWhere(['<>', 'id', $profile->isNewRecord ? null : (int)$profile->id])
            ->exists();
        if ($duplicatePhone) {
            return ['success' => false, 'message' => 'This phone number is already used by another parent.', 'profile' => null];
        }

        $transaction = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);
        try {
            if (!$profile->save()) {
                $transaction->rollBack();
                $errors = $profile->getFirstErrors();

                return [
                    'success' => false,
                    'message' => $errors !== [] ? (string)reset($errors) : 'Unable to save parent profile.',
                    'profile' => null,
                ];
            }

            $childResult = $this->syncChildren($profile);
            if (!$childResult['success']) {
                $transaction->rollBack();

                return ['success' => false, 'message' => $childResult['message'], 'profile' => null];
            }

            $credentialResult = $this->syncLoginUser($profile, $this->postedLoginPassword());
            if (!$credentialResult['success']) {
                $transaction->rollBack();

                return ['success' => false, 'message' => $credentialResult['message'], 'profile' => null];
            }

            $transaction->commit();

            $result = [
                'success' => true,
                'message' => $isCreate ? 'Parent created successfully.' : 'Parent profile updated successfully.',
                'profile' => $profile,
            ];
            if (!empty($credentialResult['credentials'])) {
                $result['credentials'] = $credentialResult['credentials'];
                $result['message'] = $isCreate
                    ? 'Parent created successfully. Login credentials are shown below — copy them now.'
                    : 'Parent profile updated. New login credentials are shown below — copy them now.';
            }

            return $result;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            Yii::error($exception->getMessage(), __METHOD__);

            $message = 'Unable to save parent profile.';
            if (stripos($exception->getMessage(), 'idx_finance_account_parent_child_profile_index') !== false
                || stripos($exception->getMessage(), 'Duplicate entry') !== false) {
                $message = 'Unable to update children order. Refresh the page and try again.';
            }

            return ['success' => false, 'message' => $message, 'profile' => null];
        }
    }

    /**
     * Create LMS login accounts for parents that do not have one yet.
     *
     * @return array{success:bool,message:string,credentials:list<array{parent:string,email:string,password:string}>}
     */
    public function provisionMissingLogins(): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Parent directory is not available.', 'credentials' => []];
        }

        $profiles = FinanceAccountParentProfile::find()
            ->where(['or', ['user_id' => null], ['user_id' => 0]])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $created = [];
        foreach ($profiles as $profile) {
            if (!$profile instanceof FinanceAccountParentProfile) {
                continue;
            }
            if (!$profile->hasAttribute('user_id')) {
                continue;
            }

            $transaction = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);
            try {
                $result = $this->syncLoginUser($profile);
                if (!$result['success']) {
                    $transaction->rollBack();

                    return [
                        'success' => false,
                        'message' => 'Failed for parent #' . (int)$profile->id . ': ' . $result['message'],
                        'credentials' => $created,
                    ];
                }
                $transaction->commit();
                if (!empty($result['credentials'])) {
                    $created[] = [
                        'parent' => $profile->getFullName(),
                        'email' => (string)$result['credentials']['email'],
                        'password' => (string)$result['credentials']['password'],
                    ];
                }
            } catch (\Throwable $exception) {
                $transaction->rollBack();
                Yii::error($exception->getMessage(), __METHOD__);

                return [
                    'success' => false,
                    'message' => 'Unable to provision login for parent #' . (int)$profile->id . ': ' . $exception->getMessage(),
                    'credentials' => $created,
                ];
            }
        }

        return [
            'success' => true,
            'message' => $created === []
                ? 'All parents already have login credentials.'
                : 'Created login credentials for ' . count($created) . ' parent(s).',
            'credentials' => $created,
        ];
    }

    /**
     * @return array{success:bool,message:string,credentials?:array{email:string,password:string}|null}
     */
    private function syncLoginUser(FinanceAccountParentProfile $profile, ?string $requestedPassword = null): array
    {
        if (!$profile->hasAttribute('user_id')) {
            return ['success' => true, 'message' => ''];
        }

        $user = null;
        if ((int)($profile->user_id ?? 0) > 0) {
            $user = User::findOne((int)$profile->user_id);
        }

        $createdUser = false;
        if ($user === null) {
            $user = new User();
            $createdUser = true;
        }

        $now = time();
        if ($user->isNewRecord && $user->hasAttribute('created_at')) {
            $user->created_at = $now;
        }
        if ($user->hasAttribute('updated_at')) {
            $user->updated_at = $now;
        }

        $loginEmail = $createdUser
            ? $this->generateUniqueLoginEmail($profile)
            : strtolower(trim((string)$user->email));

        if ($loginEmail === '' || !filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Unable to generate a valid parent login email.'];
        }

        $user->username = $loginEmail;
        $user->email = $loginEmail;
        if ($user->hasAttribute('phone') && $createdUser) {
            $user->phone = null;
        }
        if ($user->hasAttribute('role')) {
            $user->role = 'parent';
        }
        if ($user->hasAttribute('status')) {
            $user->status = User::STATUS_ACTIVE;
        }
        if ($user->hasAttribute('is_super_admin')) {
            $user->is_super_admin = 0;
        }
        if (method_exists($user, 'generateAuthKey') && ($user->isNewRecord || !$user->auth_key)) {
            $user->generateAuthKey();
        }

        $requestedPassword = trim((string)$requestedPassword);
        $currentPlain = $profile->hasAttribute('login_password_plain')
            ? trim((string)($profile->login_password_plain ?? ''))
            : '';

        $plainPassword = null;
        $passwordChanged = false;

        if ($createdUser) {
            if ($requestedPassword !== '') {
                if (strlen($requestedPassword) < 8) {
                    return ['success' => false, 'message' => 'Login password must be at least 8 characters.'];
                }
                if ($this->isSystemPasswordTaken($requestedPassword)) {
                    return ['success' => false, 'message' => 'This password is already used by another parent. Choose a different one.'];
                }
                $plainPassword = $requestedPassword;
            } else {
                $plainPassword = $this->generateUniqueSystemPassword();
            }
            $user->assignSystemPassword($plainPassword);
            $passwordChanged = true;
        } elseif ($requestedPassword !== '' && $requestedPassword !== $currentPlain) {
            if (strlen($requestedPassword) < 8) {
                return ['success' => false, 'message' => 'Login password must be at least 8 characters.'];
            }
            if ($this->isSystemPasswordTaken($requestedPassword, (int)$user->id)) {
                return ['success' => false, 'message' => 'This password is already used by another parent. Choose a different one.'];
            }
            $plainPassword = $requestedPassword;
            $user->assignSystemPassword($plainPassword);
            $passwordChanged = true;
        }

        if ($user->hasAttribute('phone') && trim((string)$user->phone) === '') {
            $user->phone = null;
        }

        if (!$user->save(false)) {
            $errors = $user->getFirstErrors();

            return [
                'success' => false,
                'message' => $errors !== [] ? (string)reset($errors) : 'Unable to create parent login credentials.',
            ];
        }

        if ((int)$profile->user_id !== (int)$user->id) {
            $profile->user_id = (int)$user->id;
        }

        if ($passwordChanged && $plainPassword !== null && $profile->hasAttribute('login_password_plain')) {
            $profile->login_password_plain = $plainPassword;
        }

        $profileAttrs = ['user_id', 'updated_at'];
        if ($profile->hasAttribute('login_password_plain') && $passwordChanged) {
            $profileAttrs[] = 'login_password_plain';
        }
        if (!$profile->save(false, $profileAttrs)) {
            return ['success' => false, 'message' => 'Unable to link parent login account.'];
        }

        $result = ['success' => true, 'message' => ''];
        if ($createdUser && $plainPassword !== null) {
            $result['credentials'] = [
                'email' => $loginEmail,
                'password' => $plainPassword,
            ];
        }

        return $result;
    }

    public function generatePreviewPassword(): string
    {
        return $this->generateUniqueSystemPassword();
    }

    /**
     * Reset visible passwords for parents that already have login users but no stored plain password.
     *
     * @return array{success:bool,message:string,credentials:list<array{parent:string,email:string,password:string}>}
     */
    public function refreshMissingVisiblePasswords(): array
    {
        $schema = Yii::$app->db->schema->getTableSchema(FinanceAccountParentProfile::tableName(), true);
        if ($schema === null || $schema->getColumn('login_password_plain') === null) {
            return ['success' => false, 'message' => 'Password column is not available. Run migrations first.', 'credentials' => []];
        }

        $profiles = FinanceAccountParentProfile::find()
            ->with(['user'])
            ->where(['not', ['user_id' => null]])
            ->andWhere(['or', ['login_password_plain' => null], ['login_password_plain' => '']])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $created = [];
        foreach ($profiles as $profile) {
            if (!$profile instanceof FinanceAccountParentProfile || !$profile->user instanceof User) {
                continue;
            }

            $password = $this->generateUniqueSystemPassword();
            $profile->user->assignSystemPassword($password);
            $profile->user->updated_at = time();
            if ($profile->user->hasAttribute('phone') && trim((string)$profile->user->phone) === '') {
                $profile->user->phone = null;
            }
            if (!$profile->user->save(false)) {
                return [
                    'success' => false,
                    'message' => 'Unable to refresh password for parent #' . (int)$profile->id . '.',
                    'credentials' => $created,
                ];
            }

            $profile->login_password_plain = $password;
            if (!$profile->save(false, ['login_password_plain', 'updated_at'])) {
                return [
                    'success' => false,
                    'message' => 'Unable to store password for parent #' . (int)$profile->id . '.',
                    'credentials' => $created,
                ];
            }

            $created[] = [
                'parent' => $profile->getFullName(),
                'email' => (string)$profile->user->email,
                'password' => $password,
            ];
        }

        return [
            'success' => true,
            'message' => $created === []
                ? 'All parent passwords are already available.'
                : 'Refreshed visible passwords for ' . count($created) . ' parent(s).',
            'credentials' => $created,
        ];
    }

    public function clearStoredLoginPasswordForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $schema = Yii::$app->db->schema->getTableSchema(FinanceAccountParentProfile::tableName(), true);
        if ($schema === null || $schema->getColumn('login_password_plain') === null) {
            return;
        }

        FinanceAccountParentProfile::updateAll(
            ['login_password_plain' => null, 'updated_at' => date('Y-m-d H:i:s')],
            ['user_id' => $userId]
        );
    }

    private function postedLoginPassword(): ?string
    {
        $password = trim((string)Yii::$app->request->post('parent_login_password', ''));

        return $password !== '' ? $password : null;
    }

    private function generateUniqueLoginEmail(FinanceAccountParentProfile $profile): string
    {
        $segments = [];
        foreach ([$profile->first_name, $profile->family_name] as $part) {
            $segment = $this->slugEmailSegment((string)$part);
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }
        if ($segments === []) {
            $segments[] = 'parent';
        }

        $baseLocal = implode('.', $segments);
        if (strlen($baseLocal) > 180) {
            $baseLocal = substr($baseLocal, 0, 180);
            $baseLocal = rtrim($baseLocal, '.');
        }

        $domain = $this->parentLoginEmailDomain();
        for ($attempt = 0; $attempt < 40; $attempt++) {
            $number = (string)random_int(1000, 999999);
            $candidate = strtolower($baseLocal . '.' . $number . '@' . $domain);
            if ($this->loginEmailConflictMessage($candidate, null) === null) {
                return $candidate;
            }
        }

        return strtolower($baseLocal . '.' . time() . random_int(10, 99) . '@' . $domain);
    }

    private function parentLoginEmailDomain(): string
    {
        $domain = trim((string)(Yii::$app->params['parentPortalEmailDomain'] ?? 'parents.culturehr.local'));

        return $domain !== '' ? $domain : 'parents.culturehr.local';
    }

    private function slugEmailSegment(string $value): string
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';

        return $value;
    }

    private function loginEmailConflictMessage(string $loginEmail, ?int $excludeUserId): ?string
    {
        $normalized = strtolower(trim($loginEmail));
        $signature = $this->emailSignature($normalized);

        $users = User::find()
            ->select(['id', 'email'])
            ->andFilterWhere(['<>', 'id', $excludeUserId])
            ->all();

        foreach ($users as $user) {
            $existing = strtolower(trim((string)$user->email));
            if ($existing === $normalized) {
                return 'This login email is already used by another account.';
            }
            if ($this->emailSignature($existing) === $signature) {
                return 'This login email is too similar to an existing account email.';
            }
        }

        return null;
    }

    private function emailSignature(string $email): string
    {
        $email = strtolower(trim($email));
        $parts = explode('@', $email, 2);
        $local = $parts[0] ?? '';
        $domain = $parts[1] ?? '';
        $plusPosition = strpos($local, '+');
        if ($plusPosition !== false) {
            $local = substr($local, 0, $plusPosition);
        }
        $local = str_replace('.', '', $local);

        return $local . '@' . $domain;
    }

    private function generateUniqueSystemPassword(): string
    {
        for ($attempt = 0; $attempt < 25; $attempt++) {
            $password = $this->randomReadablePassword();
            if (!$this->isSystemPasswordTaken($password)) {
                return $password;
            }
        }

        return $this->randomReadablePassword() . substr((string)time(), -4);
    }

    private function randomReadablePassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $length = 10;
        $max = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }

        return $password;
    }

    private function isSystemPasswordTaken(string $password, ?int $excludeUserId = null): bool
    {
        if (!User::hasColumn('system_password_fingerprint')) {
            return false;
        }

        $query = User::find()
            ->where([
                'role' => ['parent', 'student'],
                'system_password_fingerprint' => User::passwordFingerprint($password),
            ])
            ->andWhere(['password_changed_by_user' => 0]);

        if ($excludeUserId !== null && $excludeUserId > 0) {
            $query->andWhere(['<>', 'id', $excludeUserId]);
        }

        return $query->exists();
    }

    private function uploadKeyForProfile(FinanceAccountParentProfile $profile): string
    {
        $accountId = (int)($profile->finance_account_id ?? 0);
        if ($accountId > 0) {
            return (string)$accountId;
        }

        return 'p' . (int)$profile->id;
    }

    /**
     * @return array{success:bool,message:string}
     */
    private function syncChildren(FinanceAccountParentProfile $profile): array
    {
        if (!FinanceAccountParentChild::isEnabled()) {
            return ['success' => true, 'message' => ''];
        }

        $existingById = [];
        foreach ($profile->getChildren()->all() as $child) {
            if ($child instanceof FinanceAccountParentChild) {
                $existingById[(int)$child->id] = $child;
            }
        }

        $kidsCount = (int)$profile->kids_count;
        $postedChildren = [];
        $keptIds = [];

        for ($index = 1; $index <= $kidsCount; $index++) {
            $firstName = trim((string)Yii::$app->request->post('kid_first_name_' . $index, ''));
            $secondName = trim((string)Yii::$app->request->post('kid_second_name_' . $index, ''));
            $thirdName = trim((string)Yii::$app->request->post('kid_third_name_' . $index, ''));
            $familyName = trim((string)Yii::$app->request->post('kid_family_name_' . $index, ''));
            $childId = (int)Yii::$app->request->post('kid_id_' . $index, 0);

            if ($firstName === '' || $familyName === '') {
                return ['success' => false, 'message' => 'Child first name and family name are required for child #' . $index . '.'];
            }

            if ($childId > 0 && !isset($existingById[$childId])) {
                return ['success' => false, 'message' => 'Child #' . $index . ' could not be matched to an existing record. Refresh and try again.'];
            }

            if ($childId > 0) {
                if (in_array($childId, $keptIds, true)) {
                    return ['success' => false, 'message' => 'Duplicate child record submitted. Refresh and try again.'];
                }
                $keptIds[] = $childId;
            }

            $postedChildren[] = [
                'index' => $index,
                'id' => $childId,
                'first_name' => $firstName,
                'second_name' => $secondName,
                'third_name' => $thirdName,
                'family_name' => $familyName,
            ];
        }

        foreach ($existingById as $existingId => $existingChild) {
            if (in_array($existingId, $keptIds, true)) {
                continue;
            }
            if ($existingChild->hasAttribute('school_student_id') && (int)($existingChild->school_student_id ?? 0) > 0) {
                return [
                    'success' => false,
                    'message' => 'Cannot remove Child #' . (int)$existingChild->child_index . ' because they are already enrolled as a student.',
                ];
            }
        }

        // Free unique (parent, child_index) slots before reordering/creating.
        foreach ($existingById as $existingChild) {
            $tempIndex = 1000 + (int)$existingChild->id;
            if ((int)$existingChild->child_index === $tempIndex) {
                continue;
            }
            $existingChild->child_index = $tempIndex;
            if (!$existingChild->save(false, ['child_index', 'updated_at'])) {
                return ['success' => false, 'message' => 'Unable to reorder children. Please try again.'];
            }
        }

        foreach ($postedChildren as $postedChild) {
            $childId = (int)$postedChild['id'];
            if ($childId > 0) {
                $child = $existingById[$childId];
            } else {
                $child = new FinanceAccountParentChild([
                    'parent_profile_id' => (int)$profile->id,
                ]);
            }

            $child->parent_profile_id = (int)$profile->id;
            $child->child_index = (int)$postedChild['index'];
            $child->first_name = (string)$postedChild['first_name'];
            $child->second_name = $postedChild['second_name'] !== '' ? (string)$postedChild['second_name'] : null;
            $child->third_name = $postedChild['third_name'] !== '' ? (string)$postedChild['third_name'] : null;
            $child->family_name = (string)$postedChild['family_name'];

            if (!$child->save()) {
                $errors = $child->getFirstErrors();

                return [
                    'success' => false,
                    'message' => $errors !== [] ? (string)reset($errors) : 'Unable to save child #' . (int)$postedChild['index'] . '.',
                ];
            }

            if ($childId <= 0) {
                $keptIds[] = (int)$child->id;
            }
        }

        foreach ($existingById as $existingId => $existingChild) {
            if (in_array($existingId, $keptIds, true)) {
                continue;
            }
            $existingChild->delete();
        }

        return ['success' => true, 'message' => ''];
    }

    private function removeProfileFiles(FinanceAccountParentProfile $profile): void
    {
        $paths = [];
        if (trim((string)$profile->family_notebook_path) !== '') {
            $paths[] = (string)$profile->family_notebook_path;
        }
        if (trim((string)$profile->student_file_path) !== '') {
            $paths[] = (string)$profile->student_file_path;
        }

        foreach ($profile->children as $child) {
            if (!$child instanceof FinanceAccountParentChild) {
                continue;
            }
            if (trim((string)$child->birth_certificate_path) !== '') {
                $paths[] = (string)$child->birth_certificate_path;
            }
            if (trim((string)$child->student_file_path) !== '') {
                $paths[] = (string)$child->student_file_path;
            }
        }

        foreach ($paths as $path) {
            $absolute = Yii::getAlias('@webroot/' . ltrim($path, '/'));
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }

        $uploadKey = $this->uploadKeyForProfile($profile);
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $uploadKey) ?: 'parent';
        $directory = Yii::getAlias('@webroot/uploads/parents/' . $safeKey);
        if (is_dir($directory)) {
            FileHelper::removeDirectory($directory);
        }

        $legacyAccountId = (int)($profile->finance_account_id ?? 0);
        if ($legacyAccountId > 0) {
            $legacyDirectory = Yii::getAlias('@webroot/uploads/finance/parent-profiles/' . $legacyAccountId);
            if (is_dir($legacyDirectory)) {
                FileHelper::removeDirectory($legacyDirectory);
            }
        }
    }
}
