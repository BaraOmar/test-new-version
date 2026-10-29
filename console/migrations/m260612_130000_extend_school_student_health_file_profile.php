<?php

use yii\db\Migration;

class m260612_130000_extend_school_student_health_file_profile extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%school_student_health_file}}', true) === null) {
            return;
        }

        $this->addColumn('{{%school_student_health_file}}', 'primary_risk', $this->string(128)->null()->after('health_risk'));
        $this->addColumn('{{%school_student_health_file}}', 'risk_requirements', $this->text()->null()->after('primary_risk'));
        $this->addColumn('{{%school_student_health_file}}', 'allergy_criticality', $this->string(32)->notNull()->defaultValue('none')->after('allergy_detail'));
        $this->addColumn('{{%school_student_health_file}}', 'allergy_reaction', $this->text()->null()->after('allergy_criticality'));
        $this->addColumn('{{%school_student_health_file}}', 'emergency_precautions', $this->text()->null()->after('allergy_reaction'));
        $this->addColumn('{{%school_student_health_file}}', 'chronic_condition_name', $this->string(128)->null()->after('chronic_conditions'));
        $this->addColumn('{{%school_student_health_file}}', 'chronic_status', $this->string(32)->notNull()->defaultValue('controlled')->after('chronic_condition_name'));
        $this->addColumn('{{%school_student_health_file}}', 'chronic_management', $this->text()->null()->after('chronic_status'));
        $this->addColumn('{{%school_student_health_file}}', 'prior_illnesses', $this->text()->null()->after('medical_history'));
        $this->addColumn('{{%school_student_health_file}}', 'surgical_history', $this->text()->null()->after('prior_illnesses'));
        $this->addColumn('{{%school_student_health_file}}', 'significant_injuries', $this->text()->null()->after('surgical_history'));
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%school_student_health_file}}', true) === null) {
            return;
        }

        foreach ([
            'significant_injuries',
            'surgical_history',
            'prior_illnesses',
            'chronic_management',
            'chronic_status',
            'chronic_condition_name',
            'emergency_precautions',
            'allergy_reaction',
            'allergy_criticality',
            'risk_requirements',
            'primary_risk',
        ] as $column) {
            if ($this->db->schema->getTableSchema('{{%school_student_health_file}}', true)->getColumn($column) !== null) {
                $this->dropColumn('{{%school_student_health_file}}', $column);
            }
        }
    }
}
