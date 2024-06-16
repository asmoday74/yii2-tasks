<?php

namespace asmoday74\tasks\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use asmoday74\tasks\Module;

class InitController extends Controller
{
    /**
     * @inheritdoc
     */
    public $choice = null;

    /**
     * @inheritdoc
     */
    public $defaultAction = 'index';

    public function options($actionID)
    {
        return ['choice', 'color', 'interactive', 'help'];
    }

    public function actionIndex($params = null)
    {
        $version = Yii::$app->controller->module->version;
        $welcome =
            '╔════════════════════════════════════════════════╗'. "\n" .
            '║                                                ║'. "\n" .
            '║              TASKS MODULE, v.'.$version.'             ║'. "\n" .
            '║                  by asmoday74                  ║'. "\n" .
            '║                    (c) 2024                    ║'. "\n" .
            '║                                                ║'. "\n" .
            '╚════════════════════════════════════════════════╝';
        echo $name = $this->ansiFormat($welcome . "\n\n", Console::FG_GREEN);
        echo Module::t("tasks","Select the operation you want to perform:\n");
        echo Module::t("tasks","  1) Apply all module migrations\n");
        echo Module::t("tasks","  2) Revert all module migrations\n\n");
        echo Module::t("tasks","Your choice: ");

        if(!is_null($this->choice))
            $selected = $this->choice;
        else
            $selected = trim(fgets(STDIN));

        if ($selected == "1") {
            Yii::$app->runAction('migrate/up', ['migrationPath' => '@vendor/asmoday74/yii2-tasks/src/migrations', 'interactive' => true]);
        } else if($selected == "2") {
            Yii::$app->runAction('migrate/down', ['migrationPath' => '@vendor/asmoday74/yii2-tasks/src/migrations', 'interactive' => true]);
        } else {
            echo $this->ansiFormat(Module::t("tasks","Error! Your selection has not been recognized.\n\n"), Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        echo "\n";
        return ExitCode::OK;
    }
}