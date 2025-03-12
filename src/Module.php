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
     */
    public int $maxExecutionTimeDirector = 10;
    /**
     * @var int, maximum task execution time.
     * This value has priority in relation to the value established in the task itself.
     */
    public int $maxExecutionTimeWorker = 600;
    /**
     * @var int, the minimum time before the restart
     */
    public int $minTimeRestart = 60;

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
        Yii::getLogger()->log(
            '[' . $taskID . '] ' . $message,
            $level,
            get_called_class()
        );
    }

}