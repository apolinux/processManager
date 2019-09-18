<?php

use PHPUnit\Framework\TestCase;
/**
 * Daemon class test
 * 
 * isolation of daemon and taskmanager classes
 *
 * @author drake
 */
class DaemonTest extends TestCase {
    
    public function setUp() {
        $this->proc_name = substr(basename(__FILE__),0,-4);
        $this->pid_file = __DIR__ ."/../../var/$this->proc_name.pid";
        $this->options = [
          'pid_file' => $this->pid_file ,
          'log_dir' => __DIR__ ."/../../var"  ,
          'name' => $this->proc_name ,
          //'task_mode' => TaskManager::MODE_ONCE_CMD,
          //'task' => 'testTask' ,
          'run_path' => __DIR__ .'/test_task.php' ,
          //'wait_loop_task_time' => 1 ,
          'timeout_after_kill' => 15 ,
          'timeout_after_start' => 1 ,
          'stop_on_exceptions' => false ,
          /*'run_path_args' => ['param_one=niebla'],
          'run_path_env' => ['BLA' => 'FIN'],
          'php_bin' => '/usr/bin/php' , */
        ] ;
    }
    
    /**
     * $daemon = new Daemon($daemon_opts); 
     * 
     * $daemon->addTask(new Task($task_opts)); // task_opts contains command line or func/method with parameters
     * 
     * $daemon->run() ; // read command line options: "start|stop|restart" etc
     */
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
