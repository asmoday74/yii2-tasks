<?php

namespace asmoday74\tasks\models;

use asmoday74\tasks\Module as TaskModule;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use asmoday74\tasks\Module;

/**
 * This is the model class for table "task".
 *
 * @property int $id
 * @property string $name Task name
 * @property string|array $command Task run command
 * @property int $priority 10 - Low, 20 - Normal, 30 - High
 * @property int $status 10 - Wating, 20 - Queue, 30 - Progress, 40 - Complete, 50 - Unsuccessfully, 60 - Suspended, 70 - Canceled, 80 - Disabled
 * @property int $schedule_type 10 - Once, 20 - Once every day, 30 - Several times a day, 40 - Once a day weekly, 50  Several times a day weekly
 * @property string $time_launch Time or interval to start
 * @property int $day_launch Array of days of the week to run the task
 * @property int $max_execution_time Maximum task execution time
 * @property int $launch_count Count of attempts to start tasks
 * @property int $max_restarts_count maximum count of task restarts
 * @property string|null $last_run_at Ask last run time
 * @property string|null $created_at Ask created time
 * @property string|null $updated_at Ask updated time
 * @property int|null $director_pid Task Director PID
 * @property int|null $manager_pid Task Manager PID
 * @property int|null $execution_time Execution time
 *
 */
class Task extends \yii\db\ActiveRecord
{
    const TASK_STATUS_WAITING = 10;
    const TASK_STATUS_QUEUE = 20;
    const TASK_STATUS_PROGRESS = 30;
    const TASK_STATUS_COMPLETE = 40;
    const TASK_STATUS_UNSUCCESSFULLY = 50;
    const TASK_STATUS_SUSPENDED = 60;
    const TASK_STATUS_CANCELED = 70;
    const TASK_STATUS_DISABLED = 80;


    const TASK_PRIORITY_LOW = 10;
    const TASK_PRIORITY_NORMAL = 20;
    const TASK_PRIORITY_HIGH = 30;

    const TASK_PERIODIC_TYPE_ONCE = 10;
    const TASK_PERIODIC_TYPE_ONCE_DAY = 20;
    const TASK_PERIODIC_TYPE_SEVERAL_DAY = 30;
    const TASK_PERIODIC_TYPE_ONCE_DAY_WEEKLY = 40;
    const TASK_PERIODIC_TYPE_SEVERAL_DAY_WEEKLY = 50;


    const SCENARIO_EDIT_GUI = 10;

    /**
     * Virtual field for command params
     * @var string|array
     */
    public $command_params;

    /**
     * Virtual field for command class
     * @var string
     */
    public $command_class;

    public $date_start;

    public $time_start;

    public $period;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%task}}';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_EDIT_GUI] = [
            'name', 'command_class', 'command_params', 'period', 'time_launch', 'day_launch',
            'time_start', 'date_start', 'schedule_type', 'max_execution_time', 'max_restarts_count', 'priority'

        ];
        $scenarios[self::SCENARIO_DEFAULT] = ['status', 'max_execution_time', 'launch_count', 'director_pid', 'manager_pid', 'last_run_at', 'execution_time'];
        return $scenarios;
    }

    public function behaviors()
    {
        $behaviour =
            [
                [
                    'class' => AttributeBehavior::class,
                    'attributes' => [
                        self::EVENT_AFTER_FIND => 'command_params',
                    ],
                    'value' => function ($event) {
                        if ($this->command) {
                            return json_encode(ArrayHelper::getValue($this->command, 'params', []));
                        }
                    },
                ],
                [
                    'class' => AttributeBehavior::class,
                    'attributes' => [
                        self::EVENT_AFTER_FIND => 'command_class',
                    ],
                    'value' => function ($event) {
                        if ($this->command) {
                            return ArrayHelper::getValue($this->command, 'command', '');
                        }
                    },
                ],
                [
                    'class' => AttributeBehavior::class,
                    'attributes' => [
                        yii\db\BaseActiveRecord::EVENT_INIT => 'date_start',
                    ],
                    'value' => function ($event) {
                        $datetime = new \DateTime();
                        return $datetime->format('Y-m-d');
                    },
                ],
                [
                    'class' => AttributeBehavior::class,
                    'attributes' => [
                        self::EVENT_AFTER_FIND => 'date_start',
                    ],
                    'value' => function ($event) {
                        if (($this->updated_at) && ($this->schedule_type == self::TASK_PERIODIC_TYPE_ONCE)) {
                            $datetime = new \DateTime($this->updated_at);
                            $this->modifyDate($datetime, $this->time_launch);
                            return $datetime->format('Y-m-d');
                        } else {
                            return null;
                        }
                    },
                ],
                [
                    'class' => AttributeBehavior::class,
                    'attributes' => [
                        yii\db\BaseActiveRecord::EVENT_INIT => 'time_start',
                    ],
                    'value' => function ($event) {
                        $datetime = new \DateTime();
                        $datetime->modify('+1 hour');
                        return $datetime->format('H:i');
                    },
                ],
                [
                    'class' => AttributeBehavior::class,
                    'attributes' => [
                        self::EVENT_AFTER_FIND => 'time_start',
                    ],
                    'value' => function ($event) {
                        switch ($this->schedule_type) {
                            case self::TASK_PERIODIC_TYPE_ONCE:
                                if ((!$this->isNewRecord) && ($this->updated_at) && ($this->time_launch)) {
                                    $datetime = new \DateTime($this->updated_at);
                                    $this->modifyDate($datetime, $this->time_launch);
                                } elseif (!empty($this->time_start)) {
                                    $datetime = new \DateTime($this->time_start);
                                }
                                break;
                            case self::TASK_PERIODIC_TYPE_ONCE_DAY:
                            case self::TASK_PERIODIC_TYPE_ONCE_DAY_WEEKLY:
                                if ($this->time_launch) {
                                    $datetime = new \DateTime($this->time_launch);
                                }
                                break;
                        }
                        if (!isset($datetime)) {
                            $datetime = new \DateTime();
                            $datetime->modify('+1 hour');
                        }
                        return $datetime->format('H:i');
                    },
                ],
                [
                    'class' => AttributeBehavior::class,
                    'attributes' => [
                        yii\db\BaseActiveRecord::EVENT_INIT => 'period',
                    ],
                    'value' => function ($event) {
                        return '00:00:01';
                    },
                ],
                [
                    'class' => AttributeBehavior::class,
                    'attributes' => [
                        self::EVENT_AFTER_FIND => 'period',
                    ],
                    'value' => function ($event) {
                        if (($this->time_launch) && (($this->schedule_type == self::TASK_PERIODIC_TYPE_SEVERAL_DAY) || ($this->schedule_type == self::TASK_PERIODIC_TYPE_SEVERAL_DAY_WEEKLY))) {
                            $datetime = new \DateTime($this->time_launch);
                            return $datetime->format('H:i:s');
                        } else {
                            return '00:00:01';
                        }
                    },
                ],
                [
                    'class' => TimestampBehavior::class,
                    'createdAtAttribute' => 'created_at',
                    'updatedAtAttribute' => 'updated_at',
                    'value' => new Expression('NOW()'),
                ],
                [
                    'class' => AttributeBehavior::class,
                    'attributes' => [
                        self::EVENT_BEFORE_INSERT => ['status'],
                    ],
                    'value' => function ($event) {
                        return self::TASK_STATUS_WAITING;
                    },
                ],
            ];

        return ArrayHelper::merge(parent::behaviors(), $behaviour);
    }

    public function beforeSave($insert)
    {
        $command = [];
        if ($this->command_class) {
            $command['command'] = $this->command_class;
        }
        if ($this->command_params) {
            $command['params'] = is_array($this->command_params) ? $this->command_params : json_decode($this->command_params);
        }
        if (($command) && ($command != $this->command)) {
            $this->command = $command;
        }

        switch ($this->schedule_type) {
            case self::TASK_PERIODIC_TYPE_ONCE:
                if (($this->scenario == self::SCENARIO_DEFAULT)) {
                    if (($this->isNewRecord) && ($this->time_launch === true)) {
                        $this->time_launch = '00:00:01';
                    }
                } else {
                    $this->time_launch = new Expression("('$this->date_start $this->time_start' - NOW())");
                }
                break;
            case self::TASK_PERIODIC_TYPE_ONCE_DAY:
            case self::TASK_PERIODIC_TYPE_ONCE_DAY_WEEKLY:
                $this->time_launch = $this->time_start;
                break;
            case self::TASK_PERIODIC_TYPE_SEVERAL_DAY:
            case self::TASK_PERIODIC_TYPE_SEVERAL_DAY_WEEKLY:
                $this->time_launch = $this->period;
                break;
        }

        if (($this->getDirtyAttributes(['day_launch'])) || ($this->isNewRecord)) {
            if (($this->schedule_type != self::TASK_PERIODIC_TYPE_ONCE_DAY_WEEKLY) && ($this->schedule_type != self::TASK_PERIODIC_TYPE_SEVERAL_DAY_WEEKLY)) {
                $this->day_launch = '{}';
            }
        }

        return parent::beforeSave($insert);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'command_class'], 'required'],
            [['command', 'command_params', 'period', 'time_start', 'date_start', 'last_run_at', 'director_pid', 'manager_pid', 'execution_time', 'created_at', 'updated_at'], 'safe'],
            [['priority', 'status', 'schedule_type', 'pid', 'execution_time'], 'default', 'value' => null],
            [['max_execution_time', 'launch_count', 'max_restarts_count'], 'default', 'value' => 0],
            [['priority', 'status', 'schedule_type', 'max_execution_time', 'launch_count', 'max_restarts_count', 'pid', 'execution_time'], 'integer'],
            [['day_launch'], 'each', 'rule' => ['integer']],
            [['time_start'], 'date', 'format' => 'HH:mm'],
            [['name'], 'string', 'max' => 255],
            [['period'], 'required', 'when' => function ($model) {
                return (($model->schedule_type == self::TASK_PERIODIC_TYPE_SEVERAL_DAY) || ($model->schedule_type == self::TASK_PERIODIC_TYPE_SEVERAL_DAY_WEEKLY));
            }],
            [['time_start', 'date_start'], 'required', 'when' => function ($model) {
                return ($model->schedule_type == self::TASK_PERIODIC_TYPE_ONCE);
            }],
            [['time_start'], 'required', 'when' => function ($model) {
                return (($model->schedule_type == self::TASK_PERIODIC_TYPE_ONCE_DAY) || ($model->schedule_type == self::TASK_PERIODIC_TYPE_ONCE_DAY_WEEKLY));
            }],
            [['day_launch'], 'required', 'when' => function ($model) {
                return (($model->schedule_type == self::TASK_PERIODIC_TYPE_ONCE_DAY_WEEKLY) || ($model->schedule_type == self::TASK_PERIODIC_TYPE_SEVERAL_DAY_WEEKLY));
            }],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t("tasks", 'ID'),
            'name' => Yii::t("tasks", "Name"),
            'command_type' => Yii::t("tasks", "Command type"),
            'command_params' => Yii::t("tasks", "Parameters"),
            'command_class' => Yii::t("tasks", "Command"),
            'priority' => Yii::t("tasks", "Priority"),
            'status' => Yii::t("tasks", "Status"),
            'schedule_type' => Yii::t("tasks", "Launch type"),
            'date_start' => Yii::t("tasks", "Launch date"),
            'time_start' => Yii::t("tasks", "Launch time"),
            'period' => Yii::t("tasks", "Launch frequency"),
            'time_launch' => Yii::t("tasks", "Interval"),
            'day_launch' => Yii::t("tasks", "Launch days"),
            'max_execution_time' => Yii::t("tasks", "Maximum execution time"),
            'launch_count' => Yii::t("tasks", "Number of runs"),
            'max_restarts_count' => Yii::t("tasks", "Maximum number of runs"),
            'last_run_at' => Yii::t("tasks", "Last run in"),
            'pid' => Yii::t("tasks", "Workflow PID"),
            'execution_time' => Yii::t("tasks", "Task execution time"),
            'created_at' => Yii::t("tasks", "Created"),
            'updated_at' => Yii::t("tasks", "Updated"),
        ];
    }

    /**
     * Returns a list of schedule type
     *
     * @return array
     */
    public static function getScheduleTypeList(): array
    {
        return [
            self::TASK_PERIODIC_TYPE_ONCE => Yii::t("tasks", "One-time"),
            self::TASK_PERIODIC_TYPE_ONCE_DAY => Yii::t("tasks", "Every day, once a day"),
            self::TASK_PERIODIC_TYPE_SEVERAL_DAY => Yii::t("tasks", "Every day, several times a day"),
            self::TASK_PERIODIC_TYPE_ONCE_DAY_WEEKLY => Yii::t("tasks", "Weekly, once a day"),
            self::TASK_PERIODIC_TYPE_SEVERAL_DAY_WEEKLY => Yii::t("tasks", "Weekly, several times a day"),
        ];
    }

    /**
     * Returns a text description of the schedule type
     *
     * @return string
     */
    public function getScheduleTypeName(): string
    {
        return $this->getScheduleTypeList()[$this->schedule_type];
    }

    /**
     * Returns a list of priorities
     *
     * @return string[]
     */
    public static function getPriorityList(): array
    {
        return [
            self::TASK_PRIORITY_LOW => Yii::t("tasks", "Low"),
            self::TASK_PRIORITY_NORMAL => Yii::t("tasks", "Normal"),
            self::TASK_PRIORITY_HIGH => Yii::t("tasks", "High"),
        ];
    }

    /**
     * Returns a text description of the priority
     *
     * @return string
     */
    public function getPriorityName(): string
    {
        return $this->getPriorityList()[$this->priority];
    }

    /**
     * Returns a list of statuses type
     *
     * @return string[]
     */
    public static function getStatusList(): array
    {
        return [
            self::TASK_STATUS_WAITING => Yii::t("tasks", "Launch expected"),
            self::TASK_STATUS_QUEUE => Yii::t("tasks", "In queue"),
            self::TASK_STATUS_PROGRESS => Yii::t("tasks", "In progress"),
            self::TASK_STATUS_COMPLETE => Yii::t("tasks", "Successfully"),
            self::TASK_STATUS_UNSUCCESSFULLY => Yii::t("tasks", "Error"),
            self::TASK_STATUS_SUSPENDED => Yii::t("tasks", "Suspended"),
            self::TASK_STATUS_CANCELED => Yii::t("tasks", "Cancelled"),
            self::TASK_STATUS_DISABLED => Yii::t("tasks", "Disabled"),
        ];
    }

    /**
     * Returns a text description of the status
     *
     * @return string
     */
    public function getStatusName()
    {
        return $this->getStatusList()[$this->status];
    }

    /**
     * Modifies $currentDatetime by increasing or decreasing the value by $interval in PostgreeSQL format of type interval
     *
     * @param \DateTime $currentDatetime
     * @param string $interval
     * @return \DateTime
     * @throws \Exception
     */
    private function modifyDate(\DateTime &$currentDatetime, string $interval)
    {
        $isDec = false;

        $res = strpos($interval, '-');

        if (strpos($interval, '-') !== false) {
            $isDec = true;
            $interval = trim($interval, '-');
        }

        preg_match('/(?:(?:(\\d+)\\s(?:years|year)\\s)|())(?:(?:(\\d+)\\s(?:mons|mon)\\s)|())(?:(?:(\\d+)\\s(?:days|day)\\s)|())(?:(?:([01]\\d|2[0-3]):([0-5]\\d):([0-5]\\d))|())/', $interval, $matches);

        $ISO8601P = 'P';

        $ISO8601P .= empty(ArrayHelper::getValue($matches, 1)) ? '' : ArrayHelper::getValue($matches, 1) . 'Y';
        $ISO8601P .= empty(ArrayHelper::getValue($matches, 3)) ? '' : ArrayHelper::getValue($matches, 3) . 'M';
        $ISO8601P .= empty(ArrayHelper::getValue($matches, 5)) ? '' : ArrayHelper::getValue($matches, 5) . 'D';

        $ISO8601T = '';
        $ISO8601T .= empty(ArrayHelper::getValue($matches, 7)) ? '' : ArrayHelper::getValue($matches, 7) . 'H';
        $ISO8601T .= empty(ArrayHelper::getValue($matches, 8)) ? '' : ArrayHelper::getValue($matches, 8) . 'M';
        $ISO8601T .= empty(ArrayHelper::getValue($matches, 9)) ? '' : ArrayHelper::getValue($matches, 9) . 'S';
        if (!empty($ISO8601T)) {
            $ISO8601T = 'T' . $ISO8601T;
        }

        if ($isDec) {
            $currentDatetime->sub(new \DateInterval($ISO8601P . $ISO8601T));
        } else {
            $currentDatetime->add(new \DateInterval($ISO8601P . $ISO8601T));
        }

        return $currentDatetime;
    }
}
