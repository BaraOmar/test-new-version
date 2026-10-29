<?php

namespace common\modules\employee\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $school_student_id
 * @property string $health_risk
 * @property string|null $primary_risk
 * @property string|null $risk_requirements
 * @property string $allergy_status
 * @property string|null $allergy_detail
 * @property string $allergy_criticality
 * @property string|null $allergy_reaction
 * @property string|null $emergency_precautions
 * @property string|null $chronic_conditions
 * @property string|null $chronic_condition_name
 * @property string $chronic_status
 * @property string|null $chronic_management
 * @property string|null $medical_history
 * @property string|null $prior_illnesses
 * @property string|null $surgical_history
 * @property string|null $significant_injuries
 * @property string|null $last_visit_date
 * @property string|null $notes
 * @property string $created_at
 * @property string $updated_at
 */
class SchoolStudentHealthFile extends ActiveRecord
{
    public const RISK_EMERGENCY = 'emergency_risk';
    public const RISK_NORMAL = 'normal_risk';
    public const RISK_ALLERGY_WATCH = 'allergy_watch';

    public const ALLERGY_NONE = 'none';
    public const ALLERGY_MILD = 'mild';
    public const ALLERGY_SEVERE = 'severe';
    public const ALLERGY_SPECIFIC = 'specific';

    public const CRITICALITY_NONE = 'none';
    public const CRITICALITY_MILD = 'mild';
    public const CRITICALITY_MODERATE = 'moderate';
    public const CRITICALITY_CRITICAL = 'critical';

    public const CHRONIC_CONTROLLED = 'controlled';
    public const CHRONIC_UNCONTROLLED = 'uncontrolled';
    public const CHRONIC_MONITORING = 'monitoring';

    public static function tableName()
    {
        return '{{%school_student_health_file}}';
    }

    public static function isEnabled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema(static::tableName(), true) !== null;
    }

    public static function hasColumn(string $column): bool
    {
        $schema = \Yii::$app->db->schema->getTableSchema(static::tableName(), true);

        return $schema !== null && isset($schema->columns[$column]);
    }

    public static function healthRiskOptions(): array
    {
        return [
            self::RISK_EMERGENCY => 'Emergency Risk',
            self::RISK_NORMAL => 'Normal Risk',
            self::RISK_ALLERGY_WATCH => 'Allergy Watch',
        ];
    }

    public static function allergyStatusOptions(): array
    {
        return [
            self::ALLERGY_NONE => 'No Known Allergies',
            self::ALLERGY_MILD => 'Mild',
            self::ALLERGY_SEVERE => 'Severe',
            self::ALLERGY_SPECIFIC => 'Specific Allergen',
        ];
    }

    public static function allergyCriticalityOptions(): array
    {
        return [
            self::CRITICALITY_NONE => 'None',
            self::CRITICALITY_MILD => 'Mild',
            self::CRITICALITY_MODERATE => 'Moderate',
            self::CRITICALITY_CRITICAL => 'Critical',
        ];
    }

    public static function chronicStatusOptions(): array
    {
        return [
            self::CHRONIC_CONTROLLED => 'Controlled',
            self::CHRONIC_UNCONTROLLED => 'Uncontrolled',
            self::CHRONIC_MONITORING => 'Monitoring',
        ];
    }

    public function rules()
    {
        $rules = [
            [['school_student_id'], 'required'],
            [['school_student_id'], 'integer'],
            [['school_student_id'], 'unique'],
            [['school_student_id'], 'exist', 'targetClass' => SchoolStudent::class, 'targetAttribute' => ['school_student_id' => 'id']],
            [['health_risk'], 'default', 'value' => self::RISK_NORMAL],
            [['health_risk'], 'in', 'range' => array_keys(self::healthRiskOptions())],
            [['allergy_status'], 'default', 'value' => self::ALLERGY_NONE],
            [['allergy_status'], 'in', 'range' => array_keys(self::allergyStatusOptions())],
            [['allergy_detail', 'primary_risk', 'chronic_condition_name'], 'trim'],
            [['allergy_detail', 'primary_risk', 'chronic_condition_name'], 'string', 'max' => 128],
            [['chronic_conditions', 'medical_history', 'notes', 'risk_requirements', 'allergy_reaction', 'emergency_precautions', 'chronic_management', 'prior_illnesses', 'surgical_history', 'significant_injuries'], 'trim'],
            [['chronic_conditions', 'medical_history', 'notes', 'risk_requirements', 'allergy_reaction', 'emergency_precautions', 'chronic_management', 'prior_illnesses', 'surgical_history', 'significant_injuries'], 'string'],
            [['last_visit_date', 'created_at', 'updated_at'], 'safe'],
        ];

        if (self::hasColumn('allergy_criticality')) {
            $rules[] = [['allergy_criticality'], 'default', 'value' => self::CRITICALITY_NONE];
            $rules[] = [['allergy_criticality'], 'in', 'range' => array_keys(self::allergyCriticalityOptions())];
        }
        if (self::hasColumn('chronic_status')) {
            $rules[] = [['chronic_status'], 'default', 'value' => self::CHRONIC_CONTROLLED];
            $rules[] = [['chronic_status'], 'in', 'range' => array_keys(self::chronicStatusOptions())];
        }

        return $rules;
    }

    public function attributeLabels()
    {
        return [
            'health_risk' => 'Risk Level',
            'primary_risk' => 'Primary Risk',
            'risk_requirements' => 'Risk Requirements',
            'allergy_status' => 'Allergy Status',
            'allergy_detail' => 'Allergen Name',
            'allergy_criticality' => 'Criticality Level',
            'allergy_reaction' => 'Reaction Description',
            'emergency_precautions' => 'Emergency Precautions',
            'chronic_condition_name' => 'Condition Name',
            'chronic_status' => 'Condition Status',
            'chronic_management' => 'Management Notes',
            'chronic_conditions' => 'Additional Chronic Conditions',
            'prior_illnesses' => 'Prior Illnesses',
            'surgical_history' => 'Surgical History',
            'significant_injuries' => 'Significant Injuries',
            'medical_history' => 'Medical History',
            'last_visit_date' => 'Last Visit',
            'notes' => 'General Clinic Notes',
        ];
    }

    public function getStudent()
    {
        return $this->hasOne(SchoolStudent::class, ['id' => 'school_student_id']);
    }

    public function getHealthRiskLabel(): string
    {
        return self::healthRiskOptions()[$this->health_risk] ?? 'Normal Risk';
    }

    public function getPrimaryRiskLabel(): string
    {
        if (self::hasColumn('primary_risk')) {
            $label = trim((string)($this->primary_risk ?? ''));
            if ($label !== '') {
                return $label;
            }
        }

        return $this->getHealthRiskLabel();
    }

    public function getAllergyStatusLabel(): string
    {
        if (self::hasColumn('allergy_detail')) {
            $detail = trim((string)($this->allergy_detail ?? ''));
            if ($detail !== '' && $this->allergy_status !== self::ALLERGY_NONE) {
                return $detail;
            }
        }

        return match ($this->allergy_status) {
            self::ALLERGY_SEVERE => 'Severe',
            self::ALLERGY_MILD => 'Mild',
            self::ALLERGY_SPECIFIC => 'Specific Allergen',
            default => 'No Known Allergies',
        };
    }

    public function getAllergyCriticalityLabel(): string
    {
        if (!self::hasColumn('allergy_criticality')) {
            return '';
        }

        return self::allergyCriticalityOptions()[$this->allergy_criticality] ?? '';
    }

    public function getChronicStatusLabel(): string
    {
        if (!self::hasColumn('chronic_status')) {
            return 'Controlled';
        }

        return self::chronicStatusOptions()[$this->chronic_status] ?? 'Controlled';
    }

    public function getChronicConditionLabel(): string
    {
        if (self::hasColumn('chronic_condition_name')) {
            $name = trim((string)($this->chronic_condition_name ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        $tags = $this->getChronicConditionTags();

        return $tags[0] ?? '—';
    }

    /**
     * @return string[]
     */
    public function getChronicConditionTags(): array
    {
        $raw = trim((string)($this->chronic_conditions ?? ''));
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[,;\n]+/', $raw) ?: [];

        return array_values(array_filter(array_map(static fn(string $part) => trim($part), $parts)));
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (self::hasColumn('chronic_condition_name')) {
            $name = trim((string)($this->chronic_condition_name ?? ''));
            if ($name !== '' && trim((string)($this->chronic_conditions ?? '')) === '') {
                $this->chronic_conditions = $name;
            }
        }

        $now = date('Y-m-d H:i:s');
        if ($insert && !$this->created_at) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
