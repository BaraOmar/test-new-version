<?php

namespace common\modules\document\controllers;

use common\modules\document\models\Document;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class DocumentController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'upload' => ['get', 'post'],
                    'view' => ['get'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->module->ensureTables();
        return parent::beforeAction($action);
    }

    public function actionUpload()
    {
        $model = new Document();

        if ($model->load(Yii::$app->request->post())) {
            $model->uploadFile = UploadedFile::getInstance($model, 'uploadFile');
            if ($model->validate() && $model->uploadToSecureStorage($this->storageRoot()) && $model->save(false)) {
                Yii::$app->session->setFlash('success', 'Document uploaded securely.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('upload', [
            'model' => $model,
            'dataProvider' => new ActiveDataProvider([
                'query' => Document::find()->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel((int)$id);
        $path = $model->getAbsolutePath($this->storageRoot());
        if ($path === null || !is_file($path)) {
            throw new NotFoundHttpException('Document file not found.');
        }

        if (Yii::$app->request->get('download') || Yii::$app->request->get('file')) {
            return Yii::$app->response->sendFile($path, $model->original_name, [
                'mimeType' => $model->mime_type,
                'inline' => (bool)Yii::$app->request->get('file'),
            ]);
        }

        return $this->render('view', [
            'model' => $model,
            'canPreview' => $this->canPreviewInline($model),
        ]);
    }

    protected function findModel($id)
    {
        $model = Document::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Document not found.');
        }

        return $model;
    }

    protected function storageRoot()
    {
        return Yii::getAlias('@common/runtime/documents');
    }

    protected function canPreviewInline(Document $model)
    {
        return in_array($model->extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }
}
