<?php

namespace asmoday74\tasks\helpers;

use asmoday74\tasks\Module;
use Yii;
use asmoday74\tasks\models\Task;
use asmoday74\tasks\models\TaskLog;

class TaskHelper
{
    /**
     * @param int|null $pid
     *
     * @return false|mixed|string
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
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
            if ($task) {
                return $task['id'];
            } else {
                return false;
            }
        } catch (\yii\db\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @return array
     */
    public static function getProcessList()
    {
        exec("ps -ef | awk '{print $2}'", $processList);
        unset($processList[0]);
        return $processList;
    }

    /**
     * @return array
     */
    public static function getHanging(): array
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
}