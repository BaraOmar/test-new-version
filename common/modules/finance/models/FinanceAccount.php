<?php

namespace common\modules\finance\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $account_type
 * @property int|null $parent_id
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_analytical
 * @property bool $is_vat
 * @property string $created_at
 * @property string $updated_at
 *
 * @property FinanceAccount|null $parent
 * @property FinanceAccount[] $children
 */
class FinanceAccount extends ActiveRecord
{
    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_EXPENSE = 'expense';

    public const CODE_ASSET_ROOT = 10000000;
    public const CODE_ASSET_FIXED = 12000000;
    public const CODE_ASSET_CURRENT = 13000000;
    public const CODE_ASSET_RECEIVABLE = 14000000;

    /** @var int[] */
    public const ASSET_CATEGORY_CODES = [
        self::CODE_ASSET_FIXED,
        self::CODE_ASSET_CURRENT,
        self::CODE_ASSET_RECEIVABLE,
    ];

    public const CODE_LIABILITY_ROOT = 20000000;
    public const CODE_LIABILITY_PAYABLE = 230000000;
    public const CODE_LIABILITY_EQUITY = 240000000;
    public const CODE_LIABILITY_OTHER = 250000000;
    public const CODE_LIABILITY_PROVISION = 260000000;

    public const CODE_PL_ROOT = 30000000;
    public const CODE_PL_REVENUE = 31000000;
    public const CODE_PL_EXPENSE = 32000000;
    public const CODE_SCHOOL_INSTALLMENTS = '310000004';
    public const CODE_TRANSPORTATION = '310000003';
    public const CODE_OTHER_ACTIVITIES = '310000005';

    /** @var int[] */
    public const PL_CATEGORY_CODES = [
        self::CODE_PL_REVENUE,
        self::CODE_PL_EXPENSE,
    ];

    /** @var int[] */
    public const LIABILITY_CATEGORY_CODES = [
        self::CODE_LIABILITY_PAYABLE,
        self::CODE_LIABILITY_EQUITY,
        self::CODE_LIABILITY_OTHER,
        self::CODE_LIABILITY_PROVISION,
    ];

    /**
     * Codes created by finance migrations for demos, roots, or category bookkeeping.
     * They stay in the database because numbering and dropdowns rely on them.
     *
     * @return string[]
     */
    public static function systemAccountCodes(): array
    {
        return [
            '100000001',
            '100000002',
            '100000003',
            '12000000',
            '120000000',
            '13000000',
            '130000000',
            '14000000',
            '140000000',
            '200000001',
            '23000000',
            '230000000',
            '24000000',
            '240000000',
            '25000000',
            '250000000',
            '26000000',
            '260000000',
            '300000001',
            '31000000',
            '310000000',
            '32000000',
            '320000000',
            '400000001',
            '400000002',
            '500000001',
            '500000002',
            '500000003',
        ];
    }

    public static function tableName()
    {
        return '{{%finance_account}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public function rules()
    {
        return [
            [['code', 'name', 'account_type'], 'required'],
            [['code', 'name'], 'trim'],
            [['code'], 'string', 'max' => 32],
            [['name'], 'string', 'max' => 255],
            [['code'], 'match', 'pattern' => '/^\d+$/', 'message' => 'رقم الحساب يجب أن يكون رقماً فقط.'],
            [['account_type'], 'in', 'range' => [
                self::TYPE_ASSET,
                self::TYPE_LIABILITY,
                self::TYPE_EQUITY,
                self::TYPE_REVENUE,
                self::TYPE_EXPENSE,
            ]],
            [['parent_id'], 'integer'],
            [['description'], 'string'],
            [['is_active', 'is_analytical', 'is_vat'], 'boolean'],
            [['code'], 'unique'],
        ];
    }

    public function getParent()
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(self::class, ['parent_id' => 'id']);
    }

    public function getDisplayLabel(): string
    {
        return trim((string)$this->code) . ' - ' . $this->getArabicName();
    }

    public function getArabicName(): string
    {
        return (string)$this->name;
    }

    /**
     * @return array<int, string>
     */
    public static function arabicTypeLabels(): array
    {
        return [
            self::TYPE_ASSET => 'أصول',
            self::TYPE_LIABILITY => 'خصوم',
            self::TYPE_EQUITY => 'حقوق ملكية',
            self::TYPE_REVENUE => 'إيرادات',
            self::TYPE_EXPENSE => 'مصروفات',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function formTypeOptions(): array
    {
        return [
            self::TYPE_ASSET => 'موجودات (Assets)',
            self::TYPE_LIABILITY => 'مطلوبات (Liabilities)',
            'profit_loss' => 'ارباح وخسائر (Profit & Loss)',
        ];
    }

    public static function isAssetCategoryCode(int $code): bool
    {
        return in_array(self::normalizeCategoryPrefix($code), self::ASSET_CATEGORY_CODES, true);
    }

    public static function normalizeCategoryPrefix(int $code): int
    {
        return match ($code) {
            120000000 => self::CODE_ASSET_FIXED,
            130000000 => self::CODE_ASSET_CURRENT,
            140000000 => self::CODE_ASSET_RECEIVABLE,
            23000000 => self::CODE_LIABILITY_PAYABLE,
            24000000 => self::CODE_LIABILITY_EQUITY,
            25000000 => self::CODE_LIABILITY_OTHER,
            26000000 => self::CODE_LIABILITY_PROVISION,
            230000000 => self::CODE_LIABILITY_PAYABLE,
            240000000 => self::CODE_LIABILITY_EQUITY,
            250000000 => self::CODE_LIABILITY_OTHER,
            260000000 => self::CODE_LIABILITY_PROVISION,
            310000000 => self::CODE_PL_REVENUE,
            320000000 => self::CODE_PL_EXPENSE,
            default => $code,
        };
    }

    public static function isLiabilityCategoryCode(int $code): bool
    {
        return in_array(self::normalizeCategoryPrefix($code), self::LIABILITY_CATEGORY_CODES, true);
    }

    public static function isProfitLossCategoryCode(int $code): bool
    {
        return in_array(self::normalizeCategoryPrefix($code), self::PL_CATEGORY_CODES, true);
    }

    public function isLiabilityCategory(): bool
    {
        if ((string)$this->account_type !== self::TYPE_LIABILITY) {
            return false;
        }

        return self::isLiabilityCategoryCode((int)$this->code);
    }

    public function isProfitLossCategory(): bool
    {
        if (!in_array((string)$this->account_type, [self::TYPE_REVENUE, self::TYPE_EXPENSE], true)) {
            return false;
        }

        return self::isProfitLossCategoryCode((int)$this->code);
    }

    public function isSubcategoryAccount(): bool
    {
        return $this->isAssetCategory() || $this->isLiabilityCategory() || $this->isProfitLossCategory();
    }

    public static function subcategoryFieldLabel(string $accountType): string
    {
        return match ($accountType) {
            self::TYPE_ASSET => 'Assets Choices',
            self::TYPE_LIABILITY => 'Liability Types',
            default => '',
        };
    }

    public static function subcategoryEmptyOptionLabel(string $accountType): string
    {
        return match ($accountType) {
            self::TYPE_ASSET => 'بدون خيار (حساب موجودات رئيسي)',
            self::TYPE_LIABILITY => 'بدون نوع (حساب مطلوبات رئيسي)',
            default => '—',
        };
    }

    public function isAssetCategory(): bool
    {
        if ((string)$this->account_type !== self::TYPE_ASSET) {
            return false;
        }

        return self::isAssetCategoryCode((int)$this->code);
    }

    public function getArabicTypeLabel(): string
    {
        $code = (int)$this->code;
        if ($code >= self::CODE_PL_ROOT * 10 + 1 && $code < self::CODE_PL_REVENUE * 10 + 1) {
            return 'أرباح وخسائر';
        }

        return self::arabicTypeLabels()[(string)$this->account_type] ?? (string)$this->account_type;
    }

    public function getFormTypeLabel(): string
    {
        return match ((string)$this->account_type) {
            self::TYPE_ASSET => 'موجودات (Assets)',
            self::TYPE_LIABILITY => 'مطلوبات (Liabilities)',
            self::TYPE_REVENUE, self::TYPE_EXPENSE => 'ارباح وخسائر (Profit & Loss)',
            default => self::arabicTypeLabels()[(string)$this->account_type] ?? (string)$this->account_type,
        };
    }

    public function getSpecificTypeLabel(): string
    {
        if ($this->parent instanceof self) {
            return $this->parent->getArabicName();
        }

        $code = (int)$this->code;

        return match (true) {
            $code >= 120000001 && $code <= 129999999 => 'ثابتة',
            $code >= 130000001 && $code <= 139999999 => 'متداولة',
            $code >= 140000001 && $code <= 149999999 => 'ذمم مدينة',
            $code >= 230000001 && $code <= 239999999 => 'ذمم دائنة',
            $code >= 240000001 && $code <= 249999999 => 'حقوق ملكية',
            $code >= 250000001 && $code <= 259999999 => 'دائنة أخرى',
            $code >= 260000001 && $code <= 269999999 => 'مخصصات',
            $code >= 310000001 && $code <= 319999999 => 'إيرادات',
            $code >= 320000001 && $code <= 329999999 => 'مصروفات',
            default => '—',
        };
    }

    public static function schoolInstallmentsAccount(): ?self
    {
        if (!self::isEnabled()) {
            return null;
        }

        $account = self::find()
            ->where(['code' => self::CODE_SCHOOL_INSTALLMENTS, 'is_active' => true])
            ->one();

        if ($account instanceof self) {
            return $account;
        }

        $account = self::find()
            ->where(['account_type' => self::TYPE_REVENUE, 'is_active' => true])
            ->andWhere(['like', 'name', 'أقساط مدرسية'])
            ->orderBy(['code' => SORT_ASC])
            ->one();

        return $account instanceof self ? $account : null;
    }

    public static function transportationAccount(): ?self
    {
        if (!self::isEnabled()) {
            return null;
        }

        $account = self::find()
            ->where(['code' => self::CODE_TRANSPORTATION, 'is_active' => true])
            ->one();

        if ($account instanceof self) {
            return $account;
        }

        $account = self::find()
            ->where(['account_type' => self::TYPE_REVENUE, 'is_active' => true])
            ->andWhere(['like', 'name', 'مواصلات'])
            ->orderBy(['code' => SORT_ASC])
            ->one();

        return $account instanceof self ? $account : null;
    }

    public static function otherActivitiesAccount(): ?self
    {
        if (!self::isEnabled()) {
            return null;
        }

        $account = self::find()
            ->where(['code' => self::CODE_OTHER_ACTIVITIES, 'is_active' => true])
            ->one();

        if ($account instanceof self) {
            return $account;
        }

        $account = self::find()
            ->where(['account_type' => self::TYPE_REVENUE, 'is_active' => true])
            ->andWhere(['like', 'name', 'أنشطة طلابية'])
            ->orderBy(['code' => SORT_ASC])
            ->one();

        return $account instanceof self ? $account : null;
    }

    public function isSchoolInstallmentsAccount(): bool
    {
        if ((string)$this->code === self::CODE_SCHOOL_INSTALLMENTS) {
            return true;
        }

        return $this->account_type === self::TYPE_REVENUE
            && str_contains($this->getArabicName(), 'أقساط مدرسية');
    }

    public function isTransportationAccount(): bool
    {
        if ((string)$this->code === self::CODE_TRANSPORTATION) {
            return true;
        }

        return $this->account_type === self::TYPE_REVENUE
            && str_contains($this->getArabicName(), 'مواصلات');
    }

    public function isOtherActivitiesAccount(): bool
    {
        if ((string)$this->code === self::CODE_OTHER_ACTIVITIES) {
            return true;
        }

        return $this->account_type === self::TYPE_REVENUE
            && str_contains($this->getArabicName(), 'أنشطة طلابية');
    }

    public function isInvoiceRevenueRegistryAccount(): bool
    {
        return $this->isSchoolInstallmentsAccount()
            || $this->isTransportationAccount()
            || $this->isOtherActivitiesAccount();
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
