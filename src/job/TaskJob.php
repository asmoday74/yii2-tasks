<?php

namespace asmoday74\tasks\job;

use asmoday74\tasks\helpers\TaskHelper;
use asmoday74\tasks\Module;
use asmoday74\tasks\Module as TaskModule;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use asmoday74\tasks\models\Task;
use yii\log\Logger;

abstract class TaskJob extends BaseObject implements TaskJobInterface
{
    public array $params;
    public int $id;
    public bool $showLog = false;
    private TaskModule $_taskModule;

    /**
     * @param array $config
     *
     * @throws Exception
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        $this->_taskModule = TaskModule::getInstance();
        if (!ArrayHelper::keyExists('id',$config)) {
            $reflection = new \ReflectionClass($this);
            $task = new Task([
                'name' => ArrayHelper::getValue($config, 'name', 'new_task_' . date('YmdHis', time())),
                'command' => [
                    'command' => $reflection->getNamespaceName() . '\\' . $reflection->getShortName(),
                    'params' => ArrayHelper::getValue($config, 'params', [])
                ],
                'priority' => ArrayHelper::getValue($config, 'priority', Task::TASK_PRIORITY_LOW),
                'max_execution_time' => ArrayHelper::getValue($config, 'max_execution_time', 0),
                'max_restarts_count' => ArrayHelper::getValue($config, 'max_restarts_count', $this->_taskModule->defaultMaxRestartCount),
                'schedule_type' => ArrayHelper::getValue($config, 'schedule_type', Task::TASK_PERIODIC_TYPE_ONCE),
                'time_launch' => ArrayHelper::getValue($config, 'run_now', false),
                'time_start' => ArrayHelper::getValue($config, 'time_start', date('H:i', time())),
                'date_start' => ArrayHelper::getValue($config, 'date_start', date('Y-m-d', time())),
                'period' => ArrayHelper::getValue($config, 'period', '00:00:01'),
                'day_launch' => ArrayHelper::getValue($config, 'day_launch', [])
            ]);

            return $task->save();
        }

        try {
            $config['params'] = json_decode($config['params'], true);
            parent::__construct($config);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $message
     * @param int $level
     *
     * @throws InvalidConfigException
     */
    public function log(string $message, int $level = Logger::LEVEL_INFO)
    {
        if ($this->showLog) {
            TaskHelper::printLog('[taskID: ' . $this->id . '] ' . $message, $level);
        }
        Module::log($this->id, $message ,$level);
    }

}