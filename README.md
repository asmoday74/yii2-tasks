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
```php
    'modules' => [
        ...
        'tasks' => [
            'class' => 'asmoday74\tasks\Module',
            //optional config
            'jobsPath' => '@app/jobs', //path to job dir
            'maxExecutionTimeDirector' => 60, //maximum execution time of the director of the tasks
            'maxExecutionTimeWorker' => 600, //maximum task execution time. This value has priority in relation to the value established in the task itself.
            'minTimeRestart' => 60, //the minimum time before the restart of an erroneous task
            'sleepTime' => 1, //waiting time if there are no tasks, in seconds
            'deleteSuccessfulComplete' => true, //delete the task from the list after successful execution
            'deleteErrorTask' => true, //delete the task from the list after the end of attempts to execute
            'defaultMaxRestartCount' => 0 //the number of restart of the default task in case of error. 0 - endlessly
        ],
        ...
    ],

```
Migrations
------------

After configure module, run the following command in the console:

`$ php yii tasks/init`

And select the operation "**Apply all module migrations**"

or execute

```
php yii migrate --migrationPath=@vendor/asmoday74/yii2-tasks/src/migrations
```
Launching a worker to perform tasks
------------
To complete the tasks, you need to add a task to the task scheduler on the server (cron, task scheduler or etc). For example CRON:
```cronexp
* * * * * /path/to/php /your/site/dir/yii tasks/run > /dev/null 2>&1
```
This command will start the stream director to perform tasks from the list once per minute.

When starting, you can also use the following keys:  
**--single** - get the list of tasks only once (disable the loop)  
**--show-log** - output logs to the console  
**--one** - execute only the first task from the list