<?php

require_once __DIR__ .'/DaemonTestCase.php' ;
//use PHPUnit\Framework\TestCase;
use ProcessManager\ProcessDaemon\TaskManager;

/**
 * Description of DaemonCallLoopTest
 *
 * @author drake
 */
class DaemonCallLoopTest extends DaemonTestCase{
    
    public function setUp() {
        $this->dir_var = __DIR__ ."/../../var" ;
        $this->assertDirectoryExists($this->dir_var);
        $this->proc_name = substr(basename(__FILE__),0,-4);
        $this->pid_file = "$this->dir_var/$this->proc_name.pid";
        $this->options = [
          'pid_file' => $this->pid_file ,
          'log_dir' => $this->dir_var ,
          'name' => $this->proc_name ,
          'task_mode' => TaskManager::MODE_LOOP_CALL ,
          'task' => 'testTaskException' ,
          'wait_loop_task_time' => 1 ,
          'timeout_after_kill' => 15 ,
          'timeout_after_start' => 1 ,
          'stop_on_exceptions' => true ,
        ] ;
    }
    
    public function testDaemonException(){
        $this->setOptions($this->options);
        
        $this->runDaemon('start','Daemon started');
        
        $this->assertFileExists($this->pid_file, 'pid not exists') ;
        
        $pid = trim(file_get_contents($this->pid_file));
        
        $this->assertFileExists('/proc/'. $pid,
                'Process with id '. $pid.' does not exists') ;
        sleep(6);
        $this->assertFileNotExists('/proc/'. $pid,
                'Process with id '. $pid.' still running') ;
    }
}

    
    
