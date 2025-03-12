<?php

namespace asmoday74\tasks\controllers;

use Yii;
use asmoday74\tasks\models\Task;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\widgets\ActiveForm;
use asmoday74\tasks\Module;

/**
 * TaskController implements the CRUD actions for Task model.
 */
class ListController extends Controller
{

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Task models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Task::find(),
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                    'status' => SORT_ASC,
                ]
            ],
            'pagination' => [
                'pageSize' => 50
            ],

        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Task model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->renderAjax('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Task model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response|array
     */
    public function actionCreate()
    {
        $model = new Task();

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return ActiveForm::validate($model);
            }
        }

        $model->loadDefaultValues();

        return $this->renderAjax('_form', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Task model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response|array
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = Task::SCENARIO_EDIT_GUI;

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $model->status = Task::TASK_STATUS_WAITING;

            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->save()) {
                return json_encode(['status' => 'ok','message' => 'Задание успешно сохранено!']);
            } else {
                return ActiveForm::validate($model);
            }
        }

        return $this->renderAjax('_form', [
            'model' => $model,
        ]);
    }

    public function actionValidation($id = null)
    {
        if (Yii::$app->request->isAjax) {
            if ($id) {
                $model = $this->findModel($id);
            } else {
                $model = new Task();
            }

            if ($model->load(Yii::$app->request->post())) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Deletes an existing Task model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Task model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Task::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t("tasks", "The requested page does not exist"));
    }
}
