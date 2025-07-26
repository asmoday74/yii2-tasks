<?php

namespace asmoday74\tasks\commands;

use asmoday74\tasks\Module as TaskModule;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use asmoday74\tasks\helpers\TaskHelper;
use yii\helpers\ArrayHelper;
use asmoday74\tasks\models\Task;
use yii\db\Expression;
use yii\log\Logger;


class RunController extends Controller
{
    const WORKER_STATUS_SUCCESS = 0;
    const WORKER_STATUS_ERROR = 10;

    public $one;
    public $single;
    public $showLog;

    private $taskID;
    private $workerExitTime;
    /**
     * @var TaskModule
     */
    private $_taskModule;

    /**
     * {@inheritDoc}
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['one', 'single', 'showLog']);
    }

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        parent::init();
        register_shutdown_function([$this, 'shutdown']);

        $this->_taskModule = TaskModule::getInstance();

        $this->workerExitTime = time() + $this->_taskModule->maxExecutionTimeDirector;
    }

    /**
     * Default action in controller
     *
     * @return bool
     *
     * @throws \yii\db\Exception|InvalidConfigException
     */
    public function actionIndex()
    {
        while (!$this->isExit()) {
            try {
                $this->resetStatusTask();

                //Пытаемся получить задание на исполнение
                $this->taskID = TaskHelper::getJobQueue(getmypid(), $this->_taskModule->minTimeRestart);

                if (!$this->taskID) {
                    if ($this->one) {
                        break;
                    }
                    sleep($this->_taskModule->sleepTime);
                    continue;
                }

                $this->log(
                    Yii::t("tasks", "The task queued")
                );

                //Если не однопоточное выполнение, форкаем процесс
                if (!$this->single) {
                    Yii::$app->db->close();
                    $manager_pid = pcntl_fork();
                    Yii::$app->db->open();

                    if ($manager_pid === -1) {
                        $this->log(
                            Yii::t("tasks", "Fork creation is not possible, the task is executed in single-threaded mode without timeout limitation"),
                            Logger::LEVEL_WARNING
                        );
                        $this->single = true;
                    } elseif ($manager_pid === 0) {
                        return $this->roleManager();
                    }
                }

                //Если однопоточное выполнение, вызываем менеджера
                if ($this->single) {
                    $this->roleManager();
                }
                //Если нужно запустить только один раз, выходим из цикла
                if ($this->one) {
                    break;
                }

                sleep(5);
            } catch (\Exception $e) {
                $this->log(
                    Yii::t("tasks", "An error occurred while executing the task: \n{0}", $e->getMessage()),
                    Logger::LEVEL_ERROR
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Resets the status of tasks that have encountered unexpected termination of the process, updates the task status to waiting, and logs the event.
     *
     * @return void
     */
    private function resetStatusTask()
    {
        $processList = TaskHelper::getProcessList();

        //Сбрасываем зависшие в очереди задания
        $queueTask = TaskHelper::getHanging();
        $tasks = [];
        foreach ($queueTask as $task) {
            if (!ArrayHelper::isIn($task['director_pid'], $processList) && !ArrayHelper::isIn($task['manager_pid'], $processList)) {
                try {
                    $tasks[] = $task['id'];
                    $this->log(
                        Yii::t("tasks", "An unexpected termination of the task process occurred, the task status is reset. Attempt #{0}", ($task['launch_count'] + 1)),
                        Logger::LEVEL_WARNING,
                        $task['id']
                    );
                } catch (\yii\db\Exception $e) {
                    $this->log(
                        Yii::t("tasks", "An error occurred while trying to update task status.\n{0}", $e->getMessage()),
                        Logger::LEVEL_ERROR,
                        $task['id']
                    );
                }
            }
        }
        if ($tasks) {
            Task::updateAll(
                [
                    'director_pid' => null,
                    'manager_pid' => null,
                    'status' => Task::TASK_STATUS_WAITING,
                ],
                ['id' => $tasks]
            );
        }
    }

    /**
     * Manages the role for a task, including updating task status, forking worker processes, handling timeouts, and logging task completion or errors.
     *
     * @return bool Indicates whether the role management was successful or not.
     */
    private function roleManager()
    {
        try {
            Task::updateAll(
                [
                    'manager_pid' => getmypid(),
                    'status' => Task::TASK_STATUS_PROGRESS,
                    'launch_count' => new Expression('launch_count + 1'),
                    'last_run_at' => new Expression('NOW()'),
                ],
                ['id' => $this->taskID]
            );

            $this->log(
                Yii::t("tasks", "The task has been accepted for processing")
            );

            $startTimeTask = time();

            if (!$this->single) {
                Yii::$app->db->close();
                $worker_pid = pcntl_fork();
                Yii::$app->db->open();

                if ($worker_pid === -1) {
                    $this->log(
                        Yii::t("tasks", "Fork creation is not possible, the task is executed in single-threaded mode without timeout limitation"),
                        Logger::LEVEL_WARNING
                    );
                } elseif ($worker_pid === 0) {
                    return $this->roleWorker();
                }
            }

            $taskInfo = Task::find()
                ->select(['id', 'max_execution_time', 'max_restarts_count', 'schedule_type', 'status', 'launch_count', 'execution_time'])
                ->where(['id' => $this->taskID])
                ->one();

            try {
                if (($taskInfo->max_execution_time > $this->_taskModule->maxExecutionTimeWorker) || ($taskInfo->max_execution_time === 0)) {
                    $maxExecutionTimeTask = $this->_taskModule->maxExecutionTimeWorker;
                }

                if (!$this->single) {
                    $worker_pid_status = 0;
                    $worker_status = self::WORKER_STATUS_ERROR;
                    while ($worker_pid_status === 0) {
                        if ((time() - $startTimeTask) > $maxExecutionTimeTask) {
                            posix_kill($worker_pid, SIGKILL);
                            throw new \Exception(Yii::t("tasks", "The task was terminated due to a timeout"));
                        }
                        $worker_pid_status = pcntl_waitpid($worker_pid, $worker_status, WNOHANG);
                        sleep(1);
                    }
                    $worker_status = pcntl_wexitstatus($worker_status);
                } else {
                    $worker_status = $this->roleWorker();
                }

                $executionTime = time() - $startTimeTask;
                $taskInfo->execution_time = $executionTime;

                if ($worker_status === self::WORKER_STATUS_SUCCESS) {
                    switch ($taskInfo->schedule_type) {
                        case Task::TASK_PERIODIC_TYPE_ONCE:
                            $taskInfo->status = Task::TASK_STATUS_COMPLETE;
                            break;
                        default:
                            $taskInfo->status = Task::TASK_STATUS_WAITING;
                            $taskInfo->launch_count = 0;
                            break;
                    }
                    $this->log(
                        Yii::t("tasks", "Task completed successfully. Time spent: {n, duration}", ['n' => $executionTime])
                    );

                    if ($this->_taskModule->deleteSuccessfulComplete && $taskInfo->schedule_type == Task::TASK_PERIODIC_TYPE_ONCE) {
                        $taskInfo->delete();
                        unset($taskInfo);
                    }

                    return true;
                } else {
                    $taskInfo->status = Task::TASK_STATUS_UNSUCCESSFULLY;

                    $this->log(
                        Yii::t("tasks", "The task ended with errors. Time spent: {n, duration}", ['n' => $executionTime])
                    );

                    $a= $this->_taskModule->deleteErrorTask;

                    if ($this->_taskModule->deleteErrorTask && $taskInfo->max_restarts_count != 0 && $taskInfo->launch_count >= $taskInfo->max_restarts_count) {
                        $taskInfo->delete();
                        unset($taskInfo);
                    }

                    return false;
                }
            } catch (\Exception $e) {
                $taskInfo->status = Task::TASK_STATUS_UNSUCCESSFULLY;
                $this->log(
                    Yii::t("tasks", "An error occurred while executing the task: \n{0}", $e->getMessage()),
                    Logger::LEVEL_ERROR
                );
                return false;
            } finally {
                if (isset($taskInfo)) {
                    $taskInfo->director_pid = null;
                    $taskInfo->manager_pid = null;
                    $taskInfo->save();
                }
            }
        } catch (\Exception $e) {
            $this->log(
                Yii::t("tasks", "An error occurred while executing the task: \n{0}" . $e->getTraceAsString(), $e->getMessage()),
                Logger::LEVEL_ERROR
            );
            return false;
        }
    }

    /**
     * Execute the worker role for the current task.
     *
     * This method creates an instance of the task class based on the command class specified in the task information and executes it.
     *
     * @return bool|int Returns WORKER_STATUS_SUCCESS if the task executed successfully, WORKER_STATUS_ERROR otherwise.
     */
    private function roleWorker()
    {
        try {
            $taskInfo = Task::findOne($this->taskID);
            if (!mb_strpos($taskInfo->command_class, '\\')) {
                $jobPath = str_replace(['/','@'], ['\\',''], $this->_taskModule->jobsPath);
                $taskInfo->command_class = $jobPath . '\\' . $taskInfo->command_class;
            }
            $taskParams = [
                'class' => $taskInfo->command_class,
                'params' => $taskInfo->command_params,
                'id' => $this->taskID,
                'showLog' => !empty($this->showLog)
            ];
            $job = Yii::createObject($taskParams);
            $this->log(
                Yii::t("tasks", "Task class created: {0}", get_class($job)),
                Logger::LEVEL_TRACE
            );
            $this->log(
                ($taskInfo->launch_count > 1) ? Yii::t("tasks", "The task has started") : Yii::t("tasks", "Task execution started. Attempt #{0}", $taskInfo->launch_count),
            );
            if ($job->execute()) {
                return self::WORKER_STATUS_SUCCESS;
            } else {
                return self::WORKER_STATUS_ERROR;
            }
        } catch (\Exception $e) {
            $this->log(
                Yii::t("tasks", "An error occurred while executing the task: \n{0}", $e->getMessage()),
                Logger::LEVEL_ERROR
            );
            return false;
        }
    }

    /**
     * Checking to see if we need to exit the loop
     *
     * @return bool
     */
    private function isExit(): bool
    {
        if ($this->workerExitTime < time()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * The function that will be completed when the script is completed
     *
     * @return void
     */
    public function shutdown()
    {
        $error = error_get_last();

        if ($error) {
            switch ($error['type']) {
                case 1:
                    $error_type = Logger::LEVEL_ERROR;
                    break;
                case 2:
                    $error_type = Logger::LEVEL_WARNING;
                    break;
                default:
                    $error_type = false;
                    break;
            }

            if ($error_type !== false) {
                $this->log(
                    "Error: " . $error['message'] . " Line:" . $error['line'] . " File:" . $error['file'],
                    $error_type
                );
            }
        }
    }

    /**
     * @param string $message
     * @param int $level
     *
     * @throws InvalidConfigException
     */
    public function log(string $message, int $level = Logger::LEVEL_INFO, int  $taskID = null)
    {
        if (!$taskID && $this->taskID) {
            $taskID = $this->taskID;
        }

        if ($taskID) {
            TaskModule::log($taskID, $message, $level);
            TaskHelper::printLog("[taskID:$taskID]\t" . $message, $level);
        } else {
            TaskHelper::printLog($message, $level);
        }
    }

}