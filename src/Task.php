<?php


namespace ProcessManager;

/**
 * Description of Task
 *
 * @author drake
 */
class Task {
    
    private $task ;
    private $arguments ;
    
    public function __construct($task, $arguments=[]) {
        $this->task = $task ;
        $this->arguments = $arguments ;
    }
    
    public function run(){
        call_user_func_array($this->task , $this->arguments) ;
    }
}
