Yii2 Tasks Module
=================
Task manager for Yii2

Installation
------------

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist asmoday74/yii2-tasks "*"
```

or add

```
"asmoday74/yii2-tasks": "*"
```

to the require section of your `composer.json` file.

Configuration
------------

To add a module to the project, add the following data in your configuration file:

    'modules' => [
        ...
        'tasks' => [
            'class' => 'asmoday74\tasks\Module'
        ],
        ...
    ],

Migrations
------------

After configure module, run the following command in the console:

`$ php yii tasks/init`

And select the operation "**Apply all module migrations**"

or execute

```
php yii migrate --migrationPath=@vendor/asmoday74/yii2-tasks/src/migrations
```
