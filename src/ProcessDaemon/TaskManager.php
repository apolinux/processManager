<?php

namespace ProcessManager\ProcessDaemon;

/**
 * Description of Task
 *
 * @author drake
 */
class TaskManager extends Daemon{
    
    /**
     * task runs in infinite loop calling straight forward
     * uses call_user_func_array
     * uses one process
     */
    //const MODE_LOOP = 1 ;
    const MODE_LOOP_CALL = 1 ;
    
    /**
     * task runs once and finish daemon
     * uses call_user_func_array
     * uses one processes
     */
    //const MODE_ONCE = 2 ;
    const MODE_ONCE_CALL = 2 ;
    
    /**
     * task runs in infinite loop with fork
     * uses call_user_func_array
     * uses two processes
     */
    //const MODE_LOOP_FORK = 3 ;
    const MODE_LOOP_CALL_FORK = 3 ;
    
    /**
     * task runs once replacing daemon with process called from path
     * uses pcntl_exec
     * uses one process
     */
    //const MODE_ONCE_PROC = 4 ;
    const MODE_ONCE_CMD = 4 ;
    
    /**
     * task runs once with fork calling process from path
     * uses pcntl_exec
     * uses two process
     */
    //const MODE_ONCE_FORK_PROC = 5 ;
    const MODE_ONCE_CMD_FORK = 5 ;
    
    /**
     * task runs indefinitely with fork and called from path
     * uses pcntl_exec
     * uses two processes
     */
    //const MODE_LOOP_FORK_PROC = 6 ;
    const MODE_LOOP_CMD_FORK = 6 ;
    
    /**
     *
     * @var Daemon
     */
    private $daemon ;
    
    private $task ;
    
    public function __construct(Daemon $daemon){
        $this->daemon = $daemon ;
    }
    
    /**
     * call user task
     * two excecution modes: once and loop
     * @throws \Exception
     */
    public function run(){
        $this->ground_status = Logger::IS_CHILD;
        
        $this->log('Child started with pid:'. posix_getpid());
        switch($this->daemon->getConfig('task_mode')){
            case self::MODE_ONCE_CALL:
                (new RunCallable($this))->run();
                break ;
            
            case self::MODE_LOOP_CALL:
                $this->runLoop(new RunCallable($this));
                break ;
            
            case self::MODE_LOOP_CALL_FORK:
                $this->runLoop(new RunFork(new RunCallable($this))) ;
                break ;
            
            case self::MODE_ONCE_CMD:
                (new RunCmdLine($this))->run();
                break ;
            
            case self::MODE_ONCE_CMD_FORK:
                (new RunFork(new RunCmdLine($this)) )->run();
                break ;
            
            case self::MODE_LOOP_CMD_FORK:
                $this->runLoop(new RunFork(new RunCmdLine($this))) ;
                break ;
                
            default:
                $this->die("The task mode is not defined") ;
        }
    }
    
    private function runLoop(Runnable $task){
        Daemon::mustRun(true);
        
        while(Daemon::mustRun()){
            try{
                $task->run();
            }catch(\Exception $e){
                if($this->getConfig('stop_on_exceptions')){
                    $this->die( get_class($e). ' found: '. $e->getMessage() .
                            ', on line:' .$e->getLine() .
                            '. Trace: ' . $e->getTraceAsString() .
                            "\nProcess Terminated");
                }else{
                    throw $e ;
                }
            }
            self::sleep($this->getConfig('wait_loop_task_time'));
        }

        // delete pid
        $this->daemon->removePidFile() ;
        $this->log('Child finished by signal') ;
    }
    
    public function getConfig($param){
        return $this->daemon->getConfig($param);
    }
    
    public function getTask(){
        return $this->daemon->getTask();
    }
    
    public function setConfig($param, $value){
        if(property_exists($this, $param)){
            $this->$param = $value ;
        }
    }
    
    public static function sleep($time){
        if($time < 1){
            usleep($time * 1000000);
        }else{
            sleep($time);
        }
    }
}
