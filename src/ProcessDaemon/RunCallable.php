<?php

namespace ProcessManager\ProcessDaemon;

/**
 * Description of RunCallable
 *
 * @author drake
 */
class RunCallable implements Runnable{
    
    private $task_mngr ;
    
    public function __construct(TaskManager $task_mngr) {
        $this->task_mngr = $task_mngr;
    }
    
    public function setStatus($status) {
        $this->task_mngr->setConfig('ground_status', $status);
    }
    
    public function run(){
        call_user_func_array($this->task_mngr->getTask(),[]);
        Logger::log('Child finished', Logger::IS_CHILD, Logger::MODE_DEBUG) ;
    }
}
