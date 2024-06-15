<?php

namespace asmoday74\tasks\job;


interface TaskJobInterface
{
    /**
     * @return void|mixed result of the job execution
     */
    public function execute();
}
