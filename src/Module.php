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

    public function init()
    {
        parent::init();

        $this->registerTranslations();
    }

    /**
     * Registers translations for module
     */
    public function registerTranslations()
    {
        Yii::$app->i18n->translations['asmoday74/tasks'] = [
            'class'          => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath'       => '@vendor/asmoday74/yii2-tasks/src/messages',
            'fileMap'        => [
                'asmoday74/tasks' => 'tasks.php',
            ],
        ];
    }

    /**
     * Public translation function, Module::t('asmoday74/tasks', 'Hello');
     * @return string of current message translation
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('asmoday74/' . $category, $message, $params, $language);
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