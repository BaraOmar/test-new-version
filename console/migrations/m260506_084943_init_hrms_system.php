<?php

use yii\db\Migration;

class m260506_084943_init_hrms_system extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Departments table
        $this->createTable('departments', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
        ]);
    
        // Employees table
        $this->createTable('employees', [
            'id' => $this->primaryKey(),
            'full_name' => $this->string()->notNull(),
            'email' => $this->string()->unique(),
            'phone' => $this->string(),
            'job_title' => $this->string(),
            'department_id' => $this->integer(),
            'manager_id' => $this->integer(),
            'created_at' => $this->integer(),
        ]);
    
        // Foreign keys
        $this->addForeignKey(
            'fk_employee_department',
            'employees',
            'department_id',
            'departments',
            'id',
            'CASCADE'
        );
    
        $this->addForeignKey(
            'fk_employee_manager',
            'employees',
            'manager_id',
            'employees',
            'id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260506_084943_init_hrms_system cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260506_084943_init_hrms_system cannot be reverted.\n";

        return false;
    }
    */
}
