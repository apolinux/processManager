<?php

require_once __DIR__ .'/DaemonTestCase.php' ;
use ProcessManager\ProcessDaemon\TaskManager;

/**
 * Description of DaemonCallLoopTest
 *
 * @author drake
 */
class DaemonLoopCallTest extends DaemonTestCase{
    
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
          'task' => 'testTask' ,
          'wait_loop_task_time' => 1 ,
          'timeout_after_kill' => 15 ,
          'timeout_after_start' => 1 ,
          'stop_on_exceptions' => false ,
        ] ;
    }
    
    public function testDaemonStartStop(){
        $this->setOptions($this->options);
        
        $this->runDaemon('start','Daemon started');
        
        $this->assertFileExists($this->pid_file, 'pid not exists') ;
        
        $pid = trim(file_get_contents($this->pid_file));
        
        $this->assertFileExists('/proc/'. $pid,
                'Process with id '. $pid.' does not exists') ;
        
        $this->runDaemon('status','process is running with pid: '. $pid);

        $this->runDaemon('stop','Daemon stopped');
        
        $this->assertFileNotExists($pid, 'pid still exists') ;
        
        $this->assertFileNotExists('/proc/'. $pid,
                'Process with id '. $pid.' still exists after stop') ;
    }
    
    public function testDaemonRestart(){
        $this->setOptions($this->options);
        
        $this->runDaemon('start','Daemon started');
        
        $this->assertFileExists($this->pid_file, 'pid not exists') ;
        
        $pid = trim(file_get_contents($this->pid_file));
        
        $this->assertFileExists('/proc/'. $pid,
                'Process with id '. $pid.' does not exists') ;
        
        $this->runDaemon('restart','Daemon started');

        $pid = trim(file_get_contents($this->pid_file));
        
        $this->assertFileExists('/proc/'. $pid,
                'Process with id '. $pid.' does not exists') ;
        
        
        $this->runDaemon('stop','Daemon stopped');
        
        $this->assertFileNotExists($pid, 'pid still exists') ;
        
        $this->assertFileNotExists('/proc/'. $pid,
                'Process with id '. $pid.' still exists after stop') ;
    }
    
    public function testDaemonStartAfterStarted(){
        $this->setOptions($this->options);
        
        $this->runDaemon('start','Daemon started');
        
        $this->assertFileExists($this->pid_file, 'pid not exists') ;
        
        $pid = trim(file_get_contents($this->pid_file));
        
        $this->assertFileExists('/proc/'. $pid,
                'Process with id '. $pid.' does not exists') ;
        // start again
        $this->runDaemon('start','process is running with pid: '. $pid, 1);

        $this->runDaemon('stop','Daemon stopped');
        
        $this->assertFileNotExists($pid, 'pid still exists') ;
        
        $this->assertFileNotExists('/proc/'. $pid,
                'Process with id '. $pid.' still exists after stop') ;
    }
    
    public function testDaemonStopIfNotRan(){
        $this->setOptions($this->options);
        
        $this->runDaemon('stop','Daemon is not running',1);
    }
}
