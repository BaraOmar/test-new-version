<?php

use yii\db\Migration;

class m260611_140000_extend_student_enrollment_profile extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_student}}', true);
        if ($schema === null) {
            return;
        }

        $columns = [
            'blood_group' => $this->string(8)->null(),
            'religion' => $this->string(64)->null(),
            'portrait_path' => $this->string(255)->null(),
            'class_name' => $this->string(128)->null(),
            'guardian_name' => $this->string(255)->null(),
            'guardian_phone' => $this->string(32)->null(),
            'emergency_contact' => $this->string(32)->null(),
            'medical_notes' => $this->text()->null(),
        ];

        foreach ($columns as $name => $type) {
            if (!isset($schema->columns[$name])) {
                $this->addColumn('{{%school_student}}', $name, $type);
            }
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%school_student}}', true);
        if ($schema === null) {
            return;
        }

        foreach (['medical_notes', 'emergency_contact', 'guardian_phone', 'guardian_name', 'class_name', 'portrait_path', 'religion', 'blood_group'] as $column) {
            if (isset($schema->columns[$column])) {
                $this->dropColumn('{{%school_student}}', $column);
            }
        }
    }
}
