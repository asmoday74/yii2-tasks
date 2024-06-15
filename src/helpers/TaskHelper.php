<?php

namespace asmoday74\tasks\helpers;

use Yii;
use asmoday74\tasks\models\Task;
use asmoday74\tasks\models\TaskLog;

class TaskHelper
{
    public static function getJobRun(int $pid = null)
    {
        if ($pid == null) {
            return 'Error, PID value cannot be null';
        }

        try {
            $command = Yii::$app->db->createCommand("
                UPDATE " . Task::getTableSchema()->fullName . "
                SET status = :status_queue, pid = :pid
                WHERE id = (
                    SELECT id
                    FROM " . Task::getTableSchema()->fullName . "
                    WHERE (((status = :status_waiting) OR (status = :status_canceled) OR ((status = :status_unsuccessfully) AND ((max_restarts_count = 0) OR (max_restarts_count < launch_count)))) AND (
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
                RETURNING id",
                [
                    ':pid' => $pid,
                    ':status_queue' => Task::TASK_STATUS_QUEUE,
                    ':status_waiting' => Task::TASK_STATUS_WAITING,
                    ':status_canceled' => Task::TASK_STATUS_CANCELED,
                    ':status_unsuccessfully' => Task::TASK_STATUS_UNSUCCESSFULLY,
                    ':schedule_type_once' => Task::TASK_PERIODIC_TYPE_ONCE,
                    ':schedule_type_once_day' => Task::TASK_PERIODIC_TYPE_ONCE_DAY,
                    ':schedule_type_several_day' => Task::TASK_PERIODIC_TYPE_SEVERAL_DAY,
                    ':schedule_type_once_day_weekly' => Task::TASK_PERIODIC_TYPE_ONCE_DAY_WEEKLY,
                    ':schedule_type_several_day_weekly' => Task::TASK_PERIODIC_TYPE_SEVERAL_DAY_WEEKLY
                ])->queryOne();
            if ($command) {
                return $command['id'];
            } else {
                return false;
            }
        } catch (\yii\db\Exception $e) {
            return $e->getMessage();
        }
    }

    public static function getProcessList()
    {
        exec("ps -ef | awk '{print $2}'", $processList);
        unset($processList[0]);
        return $processList;
    }

    public static function getRuning()
    {
        return (new \yii\db\Query())
            ->select(['id','pid','launch_count'])
            ->from(Task::tableName())
            ->where([
                'OR',
                ['status' => Task::TASK_STATUS_QUEUE],
                ['status' => Task::TASK_STATUS_PROGRESS],
            ])
            ->all();
    }

    public static function taskLog(int $taksID, string $message, int $type = TaskLog::TASK_LOG_MESSAGE_INFO)
    {
        $taskLog = new TaskLog(['task_id' => $taksID, 'message' => $message, 'type' => $type]);
        return $taskLog->save(false);
    }
}