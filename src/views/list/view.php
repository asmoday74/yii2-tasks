<?php

use yii\widgets\DetailView;
use asmoday74\tasks\models\Task;

/** @var yii\web\View $this */
/** @var asmoday74\tasks\models\Task $model */
?>
<div class="task-view">
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'command_class',
            [
                'attribute' => 'priority',
                'value' => function ($model, $widget) {
                    return $model->priorityName;
                }
            ],
            [
                'attribute' => 'status',
                'format' => 'RAW',
                'value' => function ($model, $widget) {
                    switch ($model->status) {
                        case Task::TASK_STATUS_WAITING:
                        case Task::TASK_STATUS_QUEUE:
                        case Task::TASK_STATUS_PROGRESS:
                        case Task::TASK_STATUS_COMPLETE:
                            $class = 'text-success';
                            break;
                        case Task::TASK_STATUS_UNSUCCESSFULLY:
                            $class = 'text-danger';
                            break;
                        default:
                            $class = 'text-secondary';
                    }
                    return \yii\bootstrap5\Html::tag('span', $model->statusName, ['class' => $class]);
                }
            ],
            [
                'attribute' => 'schedule_type',
                'value' => function ($model, $widget) {
                    return $model->scheduleTypeName;
                }
            ],
            [
                'attribute' => 'max_execution_time',
                'value' => function ($model, $widget) {
                    if ($model->max_execution_time === 0) {
                        return Yii::t("tasks", "Without restrictions");
                    } else {
                        return $model->max_execution_time;
                    }
                }
            ],
            'launch_count',
            [
                'attribute' => 'max_restarts_count',
                'value' => function ($model, $widget) {
                    if ($model->max_restarts_count === 0) {
                        return Yii::t("tasks", "Without restrictions");
                    } else {
                        return $model->max_restarts_count;
                    }
                }
            ],
            'last_run_at:datetime',
            'created_at:datetime',
            'updated_at:datetime',
        ],
    ]) ?>

</div>
