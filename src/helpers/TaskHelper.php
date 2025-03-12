<?php

namespace asmoday74\tasks\helpers;

use Yii;
use asmoday74\tasks\Module as TaskModule;
use asmoday74\tasks\models\Task;
use yii\base\InvalidConfigException;
use yii\log\Logger;

/**
 * Class TaskHelper
 *
 * This class provides helper methods for managing tasks and processes.
 */
class TaskHelper
{
    /**
     * Returns the ID of the task from the job queue if found, false if not found, or a string containing the error message if an exception occurs
     * @param int $director_pid The director PID to filter by
     * @param int $min_time_restart The minimum time required for a restart
     * @return int|false|string
     * @throws InvalidConfigException
     */
    public static function getJobQueue(int $director_pid, int $min_time_restart)
    {
        try {
            $command = Yii::$app->db->createCommand("
                UPDATE " . Task::getTableSchema()->fullName . "
                SET status = :status_queue, director_pid = :director_pid
                WHERE id = (
                    SELECT id
                    FROM " . Task::getTableSchema()->fullName . "
                    WHERE (((status = :status_waiting) OR (status = :status_canceled) OR ((status = :status_unsuccessfully) AND (updated_at + :min_time_restart < NOW()) AND ((max_restarts_count = 0) OR (max_restarts_count < launch_count)))) AND (
                        (((updated_at + time_launch) < NOW()) AND (schedule_type = :schedule_type_once)) OR
                        ((time_launch < LOCALTIME) AND (schedule_type = :schedule_type_once_day)) OR
                        ((((time_launch + last_run_at) < NOW()) OR (last_run_at IS NULL)) AND (schedule_type = :schedule_type_several_day)) OR
                        (((time_launch < LOCALTIME) AND (EXTRACT(ISODOW FROM now())::INT = ANY(day_launch))) AND (DATE(last_run_at) < CURRENT_DATE) AND (schedule_type = :schedule_type_once_day_weekly)) OR
                        (((((time_launch + last_run_at) < NOW()) OR (last_run_at IS NULL)) AND (EXTRACT(ISODOW FROM now())::INT = ANY(day_launch))) AND (schedule_type = :schedule_type_several_day_weekly))
                    ))
                    ORDER BY priority DESC, id DESC
                    LIMIT 1
                    FOR UPDATE
                )
                RETURNING id",[
                ':director_pid' => $director_pid,
                'min_time_restart' => $min_time_restart,
                ':status_queue' => Task::TASK_STATUS_QUEUE,
                ':status_waiting' => Task::TASK_STATUS_WAITING,
                ':status_canceled' => Task::TASK_STATUS_CANCELED,
                ':status_unsuccessfully' => Task::TASK_STATUS_UNSUCCESSFULLY,
                ':schedule_type_once' => Task::TASK_PERIODIC_TYPE_ONCE,
                ':schedule_type_once_day' => Task::TASK_PERIODIC_TYPE_ONCE_DAY,
                ':schedule_type_several_day' => Task::TASK_PERIODIC_TYPE_SEVERAL_DAY,
                ':schedule_type_once_day_weekly' => Task::TASK_PERIODIC_TYPE_ONCE_DAY_WEEKLY,
                ':schedule_type_several_day_weekly' => Task::TASK_PERIODIC_TYPE_SEVERAL_DAY_WEEKLY
            ]);
            $task = $command->queryOne();
            return $task ? $task['id'] : false;
        } catch (\yii\db\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Returns the list of running processes on the system.
     *
     * @return array An array containing the Process IDs (PIDs) of running processes.
     */
    public static function getProcessList()
    {
        exec("ps -ef | awk '{print $2}'", $processList);
        unset($processList[0]);
        return $processList;
    }

    /**
     * Retrieves all hanging tasks from the database.
     *
     * @return array An array containing the hanging tasks with columns 'id', 'director_pid', 'manager_pid', 'launch_count'.
     */
    public static function getHanging()
    {
        return (new \yii\db\Query())
            ->select(['id', 'director_pid', 'manager_pid', 'launch_count'])
            ->from(Task::tableName())
            ->where([
                'OR',
                ['status' => Task::TASK_STATUS_QUEUE],
                ['status' => Task::TASK_STATUS_PROGRESS],
            ])
            ->all();
    }

    /**
     * Retrieves the list of available job types from the cache or generates it if not present.
     *
     * This method retrieves the list of available job types by scanning the directory defined in the TaskModule configuration.
     *
     * @return array An array containing the list of available job types where keys and values are the job type names.
     */
    public static function getJobList()
    {
        return Yii::$app->cache->getOrSet('TaskJobList', function () {
            $dir = \Yii::getAlias(TaskModule::getInstance()->jobsPath);
            $jobs = [];
            foreach(glob($dir . '/*') as $file) {
                $job = basename($file,".php");
                $jobs[$job] = $job;
            }
            return $jobs;
        }, 5 * 60);
    }

    /**
     * @param string $message
     * @param int $level
     *
     * @throws InvalidConfigException
     */
    public static function printLog(string $message, int $level = Logger::LEVEL_INFO)
    {
        echo sprintf(
            "%s %s [%s] %s\n",
            Yii::$app->formatter->asDatetime(time()),
            self::formatBytes(memory_get_usage(true)),
            mb_strtolower(Logger::getLevelName($level)),
            $message
        );
    }

    /**
     * @param integer $bytes
     *
     * @return string
     */
    private static function formatBytes(int $bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . $units[$pow];
    }

}