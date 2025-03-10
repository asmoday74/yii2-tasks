<?php

namespace asmoday74\tasks\job;


interface TaskJobInterface
{
    /**
     * @return bool result of the job execution
     */
    public function execute();
}
