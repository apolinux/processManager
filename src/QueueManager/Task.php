<?php

namespace ProcessManager\QueueManager ;

/**
 * define and run tasks
 *
 * @author drake
 */
class Task {
    
    private $task ;
    private $arguments ;
    
    /**
     * 
     * @param callable|string $task function or method to run
     * @param array $arguments arguments of task
     */
    public function __construct($task, $arguments=[]) {
        $this->task = $task ;
        $this->arguments = $arguments ;
    }
    
    /**
     * runs the task
     */
    public function run(){
        call_user_func_array($this->task , $this->arguments) ;
    }
}
