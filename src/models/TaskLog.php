<?php

namespace asmoday74\tasks\models;

use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use asmoday74\tasks\Module;

/**
 * This is the model class for table "task_log".
 *
 * @property int $id
 * @property int $task_id Task ID
 * @property string|null $message Text messages
 * @property string|null $created_at Created message time
 * @property int $type Message type: 10 - Debug, 20 - Info, 30 - Warning, 40 - Error
 *
 * @property Task $task
 */
class TaskLog extends \yii\db\ActiveRecord
{
    const TASK_LOG_MESSAGE_DEBUG = 10;
    const TASK_LOG_MESSAGE_INFO = 20;
    const TASK_LOG_MESSAGE_WARNING = 30;
    const TASK_LOG_MESSAGE_ERROR = 40;

    public function behaviors()
    {
        $behaviour = [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
        return ArrayHelper::merge(parent::behaviors(), $behaviour);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['task_id', 'type'], 'required'],
            [['task_id', 'type'], 'default', 'value' => null],
            [['task_id', 'type'], 'integer'],
            [['message'], 'string'],
            [['created_at'], 'safe'],
            [['task_id'], 'exist', 'skipOnError' => true, 'targetClass' => Task::class, 'targetAttribute' => ['task_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'            => Module::t("tasks","ID"),
            'task_id'       => Module::t("tasks","Task ID"),
            'message'       => Module::t("tasks","Message"),
            'created_at'    => Module::t("tasks","Created"),
            'type'          => Module::t("tasks","Type"),
        ];
    }

    /**
     * Gets query for [[Task]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    public static function getMessageTypes()
    {
        return [
            self::TASK_LOG_MESSAGE_DEBUG    => Module::t("tasks", "Debug"),
            self::TASK_LOG_MESSAGE_INFO     => Module::t("tasks", "Information"),
            self::TASK_LOG_MESSAGE_WARNING  => Module::t("tasks", "Warning"),
            self::TASK_LOG_MESSAGE_ERROR    => Module::t("tasks", "Error")
        ];
    }
}
