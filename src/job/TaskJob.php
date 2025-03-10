<?php

namespace asmoday74\tasks\job;

use asmoday74\tasks\helpers\TaskHelper;
use asmoday74\tasks\models\TaskLog;
use asmoday74\tasks\Module;
use Yii;
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

    /**
     * @param array $config
     *
     * @throws Exception
     * @throws \Exception
     */
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
            echo sprintf(
                "%s %s [%s] %s\n",
                Yii::$app->formatter->asDatetime(time()),
                $this->formatBytes(memory_get_usage(true)),
                mb_strtolower(Logger::getLevelName($level)),
                $message
            );
        }
        Module::log($this->id, $message ,$level);
    }

    /**
     * @param integer $bytes
     * @param integer $precision
     *
     * @return string
     */
    private function formatBytes(int $bytes, int $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . $units[$pow];
    }

}