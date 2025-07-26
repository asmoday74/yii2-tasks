<?php

namespace asmoday74\tasks;

use Yii;
use yii\helpers\Json;
use yii\log\Logger;

class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'asmoday74\tasks\controllers';
    /**
     * {@inheritdoc}
     */
    public $defaultRoute = "list";

    /**
     * @var string, the name of module
     */
    public string $name = "YII2 Tasks";

    /**
     * @var string, the description of module
     */
    public string $description = "Task manager for Yii2";

    /**
     * @var string, default path to jobs task
     */
    public string $jobsPath = '@app/jobs';

    /**
     * @var int, maximum execution time of the director of the tasks
     * Default: 60 seconds
     */
    public int $maxExecutionTimeDirector = 60;
    /**
     * @var int, maximum task execution time.
     * This value has priority in relation to the value established in the task itself.
     * Default: 600 seconds
     */
    public int $maxExecutionTimeWorker = 600;
    /**
     * @var int, the minimum time before the restart
     * Default: 60 seconds
     */
    public int $minTimeRestart = 60;
    /**
     * @var int, waiting time if there are no tasks, in seconds
     * Default: 1 second
     */
    public int $sleepTime = 1;
    /**
     * @var bool, delete the task from the list after successful execution
     * Default: true
     */
    public bool $deleteSuccessfulComplete = true;
    /**
     * @var bool, delete the task from the list after the end of attempts to execute
     * * Default: true
     */
    public bool $deleteErrorTask = true;
    /**
     * @var int, the number of restart of the default task in case of error
     * Default: 0 (infinity)
     */
    public int $defaultMaxRestartCount = 0;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        parent::init();

        Yii::$app->i18n->translations['tasks'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => '@vendor/asmoday74/yii2-tasks/src/messages',
        ];

        if (Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'asmoday74\tasks\commands';
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultVersion()
    {
        $packageInfo = Json::decode(file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'composer.json'));
        $extensionName = $packageInfo['name'];
        if (isset(Yii::$app->extensions[$extensionName])) {
            return Yii::$app->extensions[$extensionName]['version'];
        }
        return parent::defaultVersion();
    }

    /**
     * @param int $taskID
     * @param string $message
     * @param int $level
     *
     * @return void
     */
    public static function log(int $taskID, string $message, int $level = Logger::LEVEL_INFO)
    {
        if ($taskID) {
            $message = "[taskID: $taskID]\t" . $message;
        }

        Yii::getLogger()->log(
            $message,
            $level,
            get_called_class()
        );
    }
}