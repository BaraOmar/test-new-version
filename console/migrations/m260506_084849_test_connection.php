<?php

use yii\db\Migration;

class m260506_084849_test_connection extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260506_084849_test_connection cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260506_084849_test_connection cannot be reverted.\n";

        return false;
    }
    */
}
