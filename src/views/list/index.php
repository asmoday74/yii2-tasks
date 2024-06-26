<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;
use asmoday74\tasks\Module;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Module::t('tasks','Task list');
$this->params['breadcrumbs'][] = $this->title;
$this->params['actions'][] = Html::a(Module::t("tasks","Create task"), null, [
    'class' => 'btn btn-success modal-edit-toggle',
    'data' => [
        'title' => Module::t("tasks","Creating a task"),
        'url' => Url::to(['create']),
        'size' => 'modal-xl',
        'cancel' => Module::t("tasks","Cancel"),
        'submit' => Module::t("tasks","Save"),
    ]
]);
?>
<div class="task-index">
    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'attribute' => 'id',
                'headerOptions' => ['class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'attribute' => 'name',
                'content' => function ($model, $key, $index, $column) {
                    return $model->name;
                }
            ],
            [
                'attribute' => 'command',
                'content' => function ($model, $key, $index, $column) {
                    return $model->command;
                }
            ],
            [
                'attribute' => 'schedule_type',
                'content' => function ($model, $key, $index, $column) {
                    return $model->getScheduleTypeName();
                }
            ],
            [
                'attribute' => 'priority',
                'headerOptions' => ['class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center'],
                'content' => function ($model, $key, $index, $column) {
                    return $model->getPriorityName();
                }
            ],
            [
                'attribute' => 'status',
                'headerOptions' => ['class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center'],
                'content' => function ($model, $key, $index, $column) {
                    return $model->getStatusName();
                }
            ],
            //'run_in',
            //'max_execution_time:datetime',
            //'launch_count',
            //'max_restarts_count',
            //'last_run_at',
            //'created_at',
            //'updated_at',
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center'],
                'header' => Module::t("tasks",'Actions'),
                'template' => "{view}&nbsp;{update}&nbsp;{delete}",
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<span class="bi bi-eye-fill"></span>', $url, [
                            'title' => Module::t("tasks", "View")
                        ]);
                    },

                    'update' => function ($url, $model) {
                        return Html::a('<span class="bi bi-pencil-fill"></span>', null, [
                            'title' => Module::t("tasks", "Update"),
                            'data' => [
                                'title' => 'Изменение задания ' . $model->name,
                                'url' => Url::to(['update','id' => $model->id]),
                                'size' => 'xl'
                            ],
                            'class' => 'modal-edit-toggle'
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('<span class="bi bi-trash-fill"></span>', $url, [
                            'title' => Module::t("tasks", "Delete"),
                            'data' => [
                                'confirm' => Module::t("tasks", "Are you sure you want to delete this item?"),
                            ],
                        ]);
                    },
                ],
                'urlCreator' => function ($action, asmoday74\tasks\models\Task $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                }
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>
</div>