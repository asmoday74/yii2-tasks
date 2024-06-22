<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use asmoday74\tasks\Module;

/** @var yii\web\View $this */
/** @var asmoday74\tasks\models\Task $model */
/** @var yii\widgets\ActiveForm $form */

?>

<div class="task-form">

    <?php $form = ActiveForm::begin([
        'id' => 'form-modal-edit',
        'enableClientValidation' => false,
        'enableAjaxValidation' => true,
        'validationUrl' => Url::to(['list/validation']),
        ]); ?>

    <div class="form-group row">
        <div class="col-md-8">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-4">
        </div>
    </div>

    <div class="form-group row">
        <div class="col-md-4">
            <?= $form->field($model, 'command', ['options' => ['class' => '']])->dropDownList($model->getJobList()) ?>
        </div>
        <div class="col-md-8">
            <?= $form->field($model, 'command_params', ['options' => ['class' => '']])->textInput() ?>
        </div>
    </div>

    <div class="form-group row">
        <div class="col-md-4">
            <?= $form->field($model, 'priority')->dropDownList($model->getPriorityList()) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'max_execution_time')->textInput() ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'max_restarts_count')->textInput() ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <?=Module::t("tasks","Schedule the task")?>
        </div>
        <div class="card-body">
            <div class="form-group row">
                <div class="col-md-4">
                    <?= $form->field($model, 'schedule_type', ['options' => ['class' => '']])->dropDownList($model->getScheduleTypeList()) ?>
                </div>
                <div class="col-md-4 task-time_start-block" style="display: none">
                    <?= $form->field($model, 'time_start', ['options' => ['class' => '']])->input('time') ?>
                </div>
                <div class="col-md-4 task-date_start-block" style="display: none">
                    <?= $form->field($model, 'date_start', ['options' => ['class' => '']])->input('date') ?>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-4 task-period-block" style="display: none">
                    <?= $form->field($model, 'period', ['options' => ['class' => '']])->input('time', ['step' => 1]) ?>
                </div>
                <div class="col-md-8 task-day_launch-block" style="display: none">
                    <?= $form->field($model, 'day_launch', ['options' => ['class' => '']])->checkboxList(
                        [
                            '1' => Module::t("tasks","Monday"),
                            '2' => Module::t("tasks","Tuesday"),
                            '3' => Module::t("tasks","Wednesday"),
                            '4' => Module::t("tasks","Thursday"),
                            '5' => Module::t("tasks","Friday"),
                            '6' => Module::t("tasks","Saturday"),
                            '7' => Module::t("tasks","Sunday")
                        ],
                        [
                            'item' => function($index, $label, $name, $checked, $value) {
                                return Html::input('checkbox', $name, $value, ['id' => 'day_launch-'.$index ,'class' => 'btn-check', 'checked' => (bool) $checked])
                                    .Html::label($label, 'day_launch-'.$index, ['class' => 'btn btn-outline-secondary']);

                            }
                        ]);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
    function displayField(schedule_type_value) {
        const time_start = $('.task-time_start-block');
        const date_start = $('.task-date_start-block');
        const period = $('.task-period-block');
        const day_launch = $('.task-day_launch-block');
        switch (schedule_type_value) {
            case '<?=$model::TASK_PERIODIC_TYPE_ONCE?>':
                time_start.show();
                date_start.show();
                period.hide();
                day_launch.hide();
                break;
            case '<?=$model::TASK_PERIODIC_TYPE_ONCE_DAY?>':
                time_start.show();
                date_start.hide();
                period.hide();
                day_launch.hide();
                break;
            case '<?=$model::TASK_PERIODIC_TYPE_SEVERAL_DAY?>':
                time_start.hide();
                date_start.hide();
                period.show();
                day_launch.hide();
                break;
            case '<?=$model::TASK_PERIODIC_TYPE_ONCE_DAY_WEEKLY?>':
                time_start.show();
                date_start.hide();
                period.hide();
                day_launch.show();
                break;
            case '<?=$model::TASK_PERIODIC_TYPE_SEVERAL_DAY_WEEKLY?>':
                time_start.hide();
                date_start.hide();
                period.show();
                day_launch.show();
                break;
        }
    }

    $('#task-schedule_type').on('change', function () {
        displayField($(this).val());
    });
    $(document).ready(function(){
        displayField($('#task-schedule_type').val());
    });
</script>
