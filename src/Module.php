<?php

namespace asmoday74\tasks;

use Yii;
use yii\helpers\Json;


class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'asmoday74\tasks\controllers';
    /**
     * {@inheritdoc}
     */
    public $defaultRoute = "task";

    /**
     * @var string, the name of module
     */
    public $name = "YII2 Tasks";

    /**
     * @var string, the description of module
     */
    public $description = "Task manager for Yii2";

    /**
     * @var string the module version
     */
    private $version = "1.0.0";

    public function init()
    {
        parent::init();

        // Set version of current module
        $this->setVersion($this->version);

        $this->registerTranslations();
    }

    public function registerTranslations()
    {
        Yii::$app->i18n->translations['modules/tasks/*'] = [
            'class'          => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath'       => '@vendor/asmoday74/tasks/messages',
        ];
    }

    protected function defaultVersion()
    {
        $packageInfo = Json::decode(file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'composer.json'));
        $extensionName = $packageInfo['name'];
        if (isset(Yii::$app->extensions[$extensionName])) {
            return Yii::$app->extensions[$extensionName]['version'];
        }
        return parent::defaultVersion();
    }
}