<?php

use yii\db\Migration;

class m260706_150000_create_transportation_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%transport_area}}', true) === null) {
            $this->createTable('{{%transport_area}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string(255)->notNull(),
                'description' => $this->text()->null(),
                'is_active' => $this->boolean()->notNull()->defaultValue(true),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_transport_area_name', '{{%transport_area}}', 'name');
            $this->createIndex('idx_transport_area_active', '{{%transport_area}}', 'is_active');
        }

        if ($this->db->schema->getTableSchema('{{%transport_bus}}', true) === null) {
            $this->createTable('{{%transport_bus}}', [
                'id' => $this->primaryKey(),
                'plate_number' => $this->string(64)->notNull(),
                'capacity' => $this->integer()->notNull()->defaultValue(30),
                'transport_area_id' => $this->integer()->null(),
                'driver_employee_id' => $this->integer()->null(),
                'supervisor_employee_id' => $this->integer()->null(),
                'notes' => $this->text()->null(),
                'is_active' => $this->boolean()->notNull()->defaultValue(true),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_transport_bus_plate', '{{%transport_bus}}', 'plate_number', true);
            $this->createIndex('idx_transport_bus_area', '{{%transport_bus}}', 'transport_area_id');
            $this->createIndex('idx_transport_bus_driver', '{{%transport_bus}}', 'driver_employee_id');
            $this->createIndex('idx_transport_bus_supervisor', '{{%transport_bus}}', 'supervisor_employee_id');

            if ($this->db->schema->getTableSchema('{{%transport_area}}', true) !== null) {
                $this->addForeignKey(
                    'fk_transport_bus_area',
                    '{{%transport_bus}}',
                    'transport_area_id',
                    '{{%transport_area}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }

            if ($this->db->schema->getTableSchema('{{%employees}}', true) !== null) {
                $this->addForeignKey(
                    'fk_transport_bus_driver',
                    '{{%transport_bus}}',
                    'driver_employee_id',
                    '{{%employees}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
                $this->addForeignKey(
                    'fk_transport_bus_supervisor',
                    '{{%transport_bus}}',
                    'supervisor_employee_id',
                    '{{%employees}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
        }

        if ($this->db->schema->getTableSchema('{{%transport_bus_assignment}}', true) === null) {
            $this->createTable('{{%transport_bus_assignment}}', [
                'id' => $this->primaryKey(),
                'transport_bus_id' => $this->integer()->notNull(),
                'school_student_id' => $this->integer()->notNull(),
                'created_at' => $this->dateTime()->notNull(),
                'updated_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex('idx_transport_bus_assignment_bus', '{{%transport_bus_assignment}}', 'transport_bus_id');
            $this->createIndex('uq_transport_bus_assignment_student', '{{%transport_bus_assignment}}', 'school_student_id', true);

            if ($this->db->schema->getTableSchema('{{%transport_bus}}', true) !== null) {
                $this->addForeignKey(
                    'fk_transport_bus_assignment_bus',
                    '{{%transport_bus_assignment}}',
                    'transport_bus_id',
                    '{{%transport_bus}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }

            if ($this->db->schema->getTableSchema('{{%school_student}}', true) !== null) {
                $this->addForeignKey(
                    'fk_transport_bus_assignment_student',
                    '{{%transport_bus_assignment}}',
                    'school_student_id',
                    '{{%school_student}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }
        }

        $now = date('Y-m-d H:i:s');
        $areaExists = (int)$this->db->createCommand(
            'SELECT COUNT(*) FROM {{%transport_area}} WHERE [[name]] = :name',
            [':name' => 'North District']
        )->queryScalar();

        if ($areaExists === 0) {
            $this->insert('{{%transport_area}}', [
                'name' => 'North District',
                'description' => 'Northern residential pickup zone',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%transport_bus_assignment}}', true) !== null) {
            $this->dropTable('{{%transport_bus_assignment}}');
        }

        if ($this->db->schema->getTableSchema('{{%transport_bus}}', true) !== null) {
            $this->dropTable('{{%transport_bus}}');
        }

        if ($this->db->schema->getTableSchema('{{%transport_area}}', true) !== null) {
            $this->dropTable('{{%transport_area}}');
        }
    }
}
