<?php

namespace asmoday74\tasks\commands;

use Yii;
use yii\console\Controller;
use asmoday74\tasks\helpers\TaskHelper;
use yii\helpers\ArrayHelper;
use asmoday74\tasks\models\Task;
use asmoday74\tasks\models\TaskLog;
use yii\db\Expression;


class RunController extends Controller
{

    protected $pid = null;

    private $taskID;
    private $workerExitTime;

    public function init()
    {
        parent::init();
        register_shutdown_function(array($this, 'shutdown'));

        $this->pid = getmypid();
        $this->workerExitTime = time() + 60;
    }

    public function actionIndex()
    {
        while (!$this->isExit()) {
            $runningTask = TaskHelper::getRuning();
            foreach ($runningTask as $task) {
                if (!ArrayHelper::isIn($task['pid'],$runningTask)) {
                    try {
                        Task::updateAll(
                            [
                                'pid' => null,
                                'status' => Task::TASK_STATUS_WAITING,
                            ],
                            ['id' => $task['id']]
                        );
                        TaskHelper::taskLog(
                            $task['id'],
                            Yii::t("tasks", "An unexpected termination of the task process occurred, the task status is reset. Attempt #{0}", ($task['launch_count'] + 1)),
                            TaskLog::TASK_LOG_MESSAGE_WARNING
                        );
                    } catch (\yii\db\Exception $e) {
                        TaskHelper::taskLog(
                            $task['id'],
                            Yii::t("tasks", "An error occurred while trying to update task status.\n{0}", $e->getMessage()),
                            TaskLog::TASK_LOG_MESSAGE_ERROR
                        );
                    }
                }
            }

            $jobInfo = null;

            try {
                $this->taskID = TaskHelper::getJobRun($this->pid);

                if (!$this->taskID) {
                    sleep(1);
                    continue;
                }

                TaskHelper::taskLog(
                    $this->taskID,
                    Yii::t("tasks", "The task has been accepted for processing")
                );

                $startJob = time();

                Task::updateAll([
                    'status' => Task::TASK_STATUS_PROGRESS,
                    'launch_count' => new Expression('launch_count + 1'),
                    'last_run_at' => new Expression('NOW()'),
                ],
                    ['id' => $this->taskID]
                );

                $jobInfo = Task::findOne($this->taskID);
                if (strpos($jobInfo->command, '\\') == false) {
                    $namespace = "app\\jobs\\";
                } else {
                    $namespace = "";
                }
                $job_params = [
                    'class' => $namespace . $jobInfo->command,
                    'params' => $jobInfo->command_params,
                    'id' => $this->taskID
                ];

                Yii::$app->db->close();
                $pid_fork = pcntl_fork();
                Yii::$app->db->open();

                if ($pid_fork === -1) {
                    TaskHelper::taskLog(
                        $this->taskID,
                        Yii::t("tasks", "Fork creation is not possible, the task is executed in single-threaded mode without timeout limitation"),
                        TaskLog::TASK_LOG_MESSAGE_WARNING
                    );
                    $this->executeJob($job_params, $jobInfo);
                } elseif ($pid_fork !== 0) {
                    $job_pid = 0;
                    while ($job_pid === 0) {
                        if (($jobInfo->max_execution_time > 0) && ((time() - $startJob) > $jobInfo->max_execution_time)) {
                            posix_kill($pid_fork, SIGKILL);
                            throw new \Exception(Yii::t("tasks", "The task was terminated due to a timeout"));
                        }
                        $job_pid = pcntl_wait($status, WNOHANG);
                    }
                } else {
                    $this->executeJob($job_params, $jobInfo);
                    exit(0);
                }

                switch ($jobInfo->schedule_type) {
                    case Task::TASK_PERIODIC_TYPE_ONCE:
                        $jobInfo->status = Task::TASK_STATUS_COMPLETE;
                        break;
                    default:
                        $jobInfo->status = Task::TASK_STATUS_WAITING;
                        $jobInfo->launch_count = 0;
                        break;
                }

                $execution_time = time() - $startJob;
                TaskHelper::taskLog(
                    $this->taskID,
                     Yii::t("tasks", "Task completed successfully. Time spent: {n, duration}",['n' => $execution_time])
                );
                $jobInfo->execution_time = $execution_time;
            } catch (\Exception $e) {
                $jobInfo->status = Task::TASK_STATUS_UNSUCCESSFULLY;
                TaskHelper::taskLog(
                    $this->taskID,
                    Yii::t("tasks", "An error occurred while executing the task: \n{0}", $e->getMessage()),
                    TaskLog::TASK_LOG_MESSAGE_ERROR
                );
            } finally {
                if ($jobInfo) {
                    $jobInfo->pid = null;
                    $jobInfo->save(false);
                }
            }
        }
    }

    private function executeJob($jobParams, $jobInfo) {
        try {
            $job = Yii::createObject($jobParams);
            TaskHelper::taskLog(
                $this->taskID,
                Yii::t("tasks", "Task class created: {0}", get_class($job)),
                TaskLog::TASK_LOG_MESSAGE_DEBUG
            );
            TaskHelper::taskLog(
                $this->taskID,
                ($jobInfo->launch_count > 1) ? Yii::t("tasks", "The task has started") : Yii::t("tasks", "Task execution started. Attempt #{0}", $jobInfo->launch_count),
            );
            $job->execute();
        } catch (\Exception $e) {
            TaskHelper::taskLog(
                $this->taskID,
                Yii::t("tasks", "An error occurred while executing the task: \n{0}", $e->getMessage()),
                TaskLog::TASK_LOG_MESSAGE_ERROR
            );
        }
    }

    private function isExit()
    {
        if ($this->workerExitTime < time()) {
            return true;
        } else {
            return false;
        }
    }

    public function shutdown()
    {
        $error = error_get_last();

        if ($error) {
            switch ($error['type']) {
                case 1: $error_type = TaskLog::TASK_LOG_MESSAGE_ERROR; break;
                case 2: $error_type = TaskLog::TASK_LOG_MESSAGE_WARNING; break;
                default: $error_type = TaskLog::TASK_LOG_MESSAGE_DEBUG; break;
            }
            TaskHelper::taskLog(
                $this->taskID,
                "Error: " . $error['message'] . " Line:" . $error['line'] . " File:" . $error['file'],
                $error_type
            );
        }
    }
}