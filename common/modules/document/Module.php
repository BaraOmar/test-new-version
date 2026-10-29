<?php

namespace common\modules\document;

use common\modules\document\models\Document;
use Yii;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    public $controllerNamespace = 'common\modules\document\controllers';

    public function ensureTables()
    {
        $db = Yii::$app->db;
        if ($db->schema->getTableSchema(Document::tableName(), true) !== null) {
            return;
        }

        $db->createCommand()->createTable(Document::tableName(), [
            'id' => 'pk',
            'title' => 'varchar(255) NOT NULL',
            'description' => 'text NULL',
            'original_name' => 'varchar(255) NOT NULL',
            'stored_name' => 'varchar(255) NOT NULL',
            'storage_path' => 'varchar(500) NOT NULL',
            'extension' => 'varchar(16) NOT NULL',
            'mime_type' => 'varchar(150) NOT NULL',
            'file_size' => 'integer NOT NULL',
            'sha256_hash' => 'varchar(64) NOT NULL',
            'uploaded_by' => 'integer NULL',
            'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
        ])->execute();

        $db->createCommand()->createIndex('idx_documents_uploaded_by', Document::tableName(), 'uploaded_by')->execute();
        $db->createCommand()->createIndex('idx_documents_extension', Document::tableName(), 'extension')->execute();
        $db->createCommand()->createIndex('idx_documents_created_at', Document::tableName(), 'created_at')->execute();
    }
}
