<?php

namespace ProcessManager;

/**
 * Manage task forking and communication between them
 * 
 * run tasks in background and lets to communicate using a message queue like:
 * beanstalk
 *
 * @author Carlos Arce <apolinux@gmail.com>
 */
class QueueManager {
    
    const WAIT_TASKS_TIME = 1 ;
    
    /**
     * messages queue
     * @var Queueable
     */
    private $queue ;
    
    /**
     * pid task list
     * @var array
     */
    private $task_list ;
    
    private $default_options = [
      'wait_tasks_time' => self::WAIT_TASKS_TIME ,
      'restart_ended_task' => false , // if true, restart ended tasks if error
      'restarting_return_code_min' => 2 , // minimal return code of task ended to let restarting
    ];
    
    private $options  = [];
    
    /**
     * constructor 
     * 
     * @param \ProcessManager\Queueable $queue messages queue
     */
    public function __construct(Queueable $queue, array $options=[]) {
        $this->queue = $queue ;
        $this->options = array_merge($this->default_options , (array)$options) ;
    }
    
    /**
     * add new task consumer or producer
     * 
     * @param callable $task task to run forked
     * @param array $params parameters of callable
     */
    public function addTask(callable $task, array $params=[]){
        $params = array_merge([$this] , $params) ;
        $this->task_list[] = new Task($task,$params) ;
    }
    
    private $task_run_list ;
    
    /**
     * run tasks in background
     */
    public function run(){
        //$task_pid_list = [] ;
        $this->task_run_list = [];
        foreach($this->task_list as $index => $task){
            $this->runTask($task, $index);
        }
        
        $this->waitForTasks();
    }
    
    /**
     * fork and run task in background
     * @param \ProcessManager\Task $task
     * @return int
     */
    private function runTask(Task $task, $index){
        $pid = pcntl_fork();
        if($pid == -1){
            die('Error forking') ;
        }
        if($pid == 0){
            $task->run();
            exit(0) ;
        }
        $this->task_run_list[$index] = $pid ;
        return $pid ;
    }
    
    /**
     * wait to finish tasks
     * 
     * @param array $pid_list
     */
    //private function waitForTasks($pid_list){
    private function waitForTasks(){
        while(count($this->task_run_list) > 0) {
            foreach($this->task_run_list as $task_idx => $pid) {
                $this->checkTask($task_idx, $pid);
            }
            sleep($this->getOption('wait_tasks_time'));
        }
    }
    
    /**
     * do an action if task process has ended
     * 
     * @param int $task_idx task index
     * @param int $pid process id of task forked
     * @return void
     */
    private function checkTask($task_idx, $pid){
        $res = pcntl_waitpid($pid, $status, WNOHANG);
        
        if($this->getOption('restart_ended_task')){
            if( $this->restartTask($task_idx,$res, $status)){
                return ;
            }
        }
        // If the process has already exited
        if($res == -1 || $res > 0){
            unset($this->task_run_list[$task_idx]);
            echo "child with pid $pid exited with status:". pcntl_wexitstatus($status) ."\n" ;
        }
    }
    
    /**
     * restart task when is necessary
     * 
     * restart task when ended and return code is greater than a minimal defined
     * 
     * @param int $task_idx task index in task list
     * @param int $res result of pcntl_waitpid, maybe -1, 0 or process id of task
     * @param int $status status information about process
     * @return boolean  true if task was restarted
     */
    private function restartTask($task_idx, $res, $status){
        $statusc = pcntl_wexitstatus($status);
        // only restart if ther was an error when child ended
        if( ( $res == -1 || $res > 0 ) && ( $statusc > $this->getOption('restarting_return_code_min') ) ){
            echo "restarting task $task_idx \n";
            $task = $this->task_list[$task_idx];
            $this->runTask($task, $task_idx);
            return true ;
        }
        return false ;
    }
    
    public function getOption($option){
        return $this->options[$option] ?? $this->getDefaultOption($option) ;
    }
    
    public function getDefaultOption($option){
        return $this->default_options[$option] ?? null ;
    }
    
    public function setOption($option, $value){
        $this->options[$option] = $value ;
    }
    
    /**
     * send message to queue
     * @param mixed $msg
     */
    public function sendMsg($msg){
        $this->queue->sendMsg($msg) ;
    }
    
    /**
     * read message from queue
     * @return mixed
     */
    public function readMsg(){
        return $this->queue->readMsg();
    }
}
