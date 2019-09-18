<?php

namespace Apolinux\PlatformTools\Process;

/**
 * Description of RunCallable
 *
 * @author drake
 */
class RunCmdLine implements Runnable{
    
    private $task_mngr ;
    
    public function __construct(TaskManager $task_mngr) {
        $this->task_mngr = $task_mngr;
    }
    
    public function setStatus($status) {
        $this->task_mngr->setConfig('ground_status', $status);
    }
    
    public function run(){
        $args = array_merge([ '-d display_errors=1' ,
                      $this->task_mngr->getConfig('run_path')  ,
                    ]  , 
                (array)$this->task_mngr->getConfig('run_path_args')
                );
        $env = (array)$this->task_mngr->getConfig('run_path_env')  ;
        
        Logger::log(json_encode([
          'method' => __METHOD__ ,
          'pcntl_exec_params' => [
            $this->task_mngr->getConfig('php_bin') ,
            $args , 
            $env ,
          ]]),Logger::IS_CHILD, Logger::MODE_DEBUG) ;
        
        pcntl_exec($this->task_mngr->getConfig('php_bin') , $args , $env) ;
        $this->task->die('Error running process:'. json_encode(error_get_last()) );
    }
}
