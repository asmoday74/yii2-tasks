<?php

namespace asmoday74\tasks\job;

use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use asmoday74\tasks\models\Task;

abstract class TaskJob extends BaseObject implements TaskJobInterface
{
    public $params;

    public $id;

    public function __construct($config = [])
    {

        if (!ArrayHelper::keyExists('id',$config)) {
            $reflection = new \ReflectionClass($this);

            $namespace = $reflection->getNamespaceName();
            $command = $reflection->getShortName();
            if ($namespace != 'app\\jobs') {
                $command = $namespace . '\\' . $command;
            }
            $task = new Task([
                'name' => ArrayHelper::getValue($config, 'name', 'new_task_' . date('YmdHis', time())),
                'command' => $command,
                'command_params' => ArrayHelper::getValue($config, 'params', []),
                'priority' => ArrayHelper::getValue($config, 'priority', Task::TASK_PRIORITY_LOW),
                'max_execution_time' => ArrayHelper::getValue($config, 'max_execution_time', 0),
                'max_restarts_count' => ArrayHelper::getValue($config, 'max_restarts_count', 0),
                'schedule_type' => ArrayHelper::getValue($config, 'schedule_type', Task::TASK_PERIODIC_TYPE_ONCE),
                'time_start' => ArrayHelper::getValue($config, 'time_start', date('H:i', time())),
                'date_start' => ArrayHelper::getValue($config, 'date_start', date('Y-m-d', time())),
                'period' => ArrayHelper::getValue($config, 'period', '00:00:01'),
                'day_launch' => ArrayHelper::getValue($config, 'day_launch', [])
            ]);
            return $task->save();
        }

        $config['params'] = json_decode($config['params'], true);

        return parent::__construct($config);
    }

}