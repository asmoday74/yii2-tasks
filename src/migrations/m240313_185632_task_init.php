<?php

use yii\db\Migration;

/**
 * Class m240313_185632_task_init
 */
class m240313_185632_task_init extends Migration
{
    /**
     * {@inheritdoc}
     */
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->createTable('{{%task}}', [
            'id' => $this->bigPrimaryKey(),
            'name' => $this->string()->notNull()->comment('Task name'),
            'command' => $this->json()->notNull()->comment('Task run command'),
            'priority' => $this->integer()->notNull()->defaultValue(10)->comment('10 - Low, 20 - Normal, 30 - High'),
            'status' => $this->integer()->notNull()->defaultValue(10)->comment('10 - Wating, 20 - Queue, 30 - Progress, 40 - Complete, 50 - Unsuccessfully, 60 - Suspended, 70 - Canceled, 80 - Disabled'),
            'schedule_type' => $this->integer()->notNull()->defaultValue(10)->comment('10 - Once, 20 - Once every day, 30 - Several times a day, 40 - Once a day weekly, 50  Several times a day weekly'),
            'time_launch' => "INTERVAL NOT NULL",
            'day_launch' => "INTEGER[] NOT NULL DEFAULT '{}'",
            'max_execution_time' => $this->integer()->notNull()->defaultValue(0)->comment('Maximum task execution time'),
            'launch_count' => $this->integer()->notNull()->defaultValue(0)->comment('Count of attempts to start tasks'),
            'max_restarts_count' => $this->integer()->notNull()->defaultValue(0)->comment('maximum count of task restarts'),
            'last_run_at' => $this->timestamp()->defaultValue(null)->comment('Ask last run time'),
            'pid' => $this->integer()->defaultValue(null)->comment('PID worker'),
            'execution_time' => $this->integer()->defaultValue(null)->comment('Execution time'),
            'created_at' => $this->timestamp()->defaultValue(null)->comment('Ask created time'),
            'updated_at' => $this->timestamp()->defaultValue(null)->comment('Ask updated time'),
        ]);
        $this->addCommentOnColumn('{{%task}}', 'time_launch', 'Time or interval to start');
        $this->addCommentOnColumn('{{%task}}', 'day_launch', 'Array of days of the week to run the task');
        $this->addCommentOnTable('{{%task}}','Task list for cron');

        $this->createTable('{{%task_log}}', [
            'id' => $this->bigPrimaryKey(),
            'task_id' => $this->integer()->notNull()->comment('Task ID'),
            'type' => $this->integer()->notNull()->comment('Message type: 10 - Debug, 20 - Info, 30 - Warning, 40 - Error'),
            'message' => $this->text()->notNull()->comment('Text messages'),
            'created_at' => $this->timestamp()->notNull()->comment('Created message time'),
        ]);
        $this->addCommentOnTable('{{%task_log}}','task logs');
        $this->createIndex('idx-task_log-task_id', '{{%task_log}}', 'task_id');
        $this->addForeignKey(
            'fk-task_id-task-id',
            '{{%task_log}}',
            'task_id',
            '{{%task}}',
            'id',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropForeignKey('fk-task_id-task-id', '{{%task_log}}');
        $this->dropIndex('idx-task_log-task_id', '{{%task_log}}');
        $this->dropTable('{{%task_log}}');

        $this->dropTable('{{%task}}');
    }
}
