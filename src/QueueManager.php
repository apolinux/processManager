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
      'wait_tasks_time' => self::WAIT_TASKS_TIME
    ];
    
    private $options  = [];
    
    /**
     * constructor 
     * 
     * @param \ProcessManager\Queueable $queue messages queue
     */
    public function __construct(Queueable $queue, array $options=[]) {
        $this->queue = $queue ;
        $this->options = $this->default_options + (array)$options ;
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
    
    /**
     * run tasks in background
     */
    public function run(){
        $task_pid_list = [] ;
        foreach($this->task_list as $task){
            $task_pid_list[] = $this->runTask($task);
        }
        
        $this->waitForTasks($task_pid_list);
    }
    
    /**
     * fork and run task in background
     * @param \ProcessManager\Task $task
     * @return int
     */
    private function runTask(Task $task){
        $pid = pcntl_fork();
        if($pid == -1){
            die('Error forking') ;
        }
        if($pid == 0){
            $task->run();
            exit(0) ;
        }
        return $pid ;
    }
    
    /**
     * wait to finish tasks
     * 
     * @param array $pid_list
     */
    private function waitForTasks(array $pid_list){
        while(count($pid_list) > 0) {
            foreach($pid_list as $key => $pid) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                // If the process has already exited
                if($res == -1 || $res > 0){
                    unset($pid_list[$key]);
                    echo "child with pid $pid exited with status:". pcntl_wexitstatus($status) ."\n" ;
                }
            }
            sleep($this->getOption('wait_tasks_time'));
        }
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
