<?php
use ProcessManager\ProcessDaemon\Daemon;
use ProcessManager\ProcessDaemon\TaskManager;

require __DIR__ .'/../../bootstrap.php' ;
require __DIR__ .'/../../../vendor/autoload.php' ;
        
$daemon = new Daemon([
  'pid_file' => __DIR__ .'/../../var/testDaemonOnceProc.pid' ,
  'log_dir' => __DIR__  .'/../../var',
  'name' => 'testDaemonOnceProc' ,
  'task_mode' => TaskManager::MODE_ONCE_CMD,
  //'task' => 'testTask',
  'run_path' => __DIR__ .'/test_task.php' ,
  'run_path_args' => ['param_one=niebla','param2="tucutu"'],
  'run_path_env' => ['BLA' =>'FIN'],
  'php_bin' => '/usr/bin/php' ,
  //'log_mode' => Logger::MODE_DEBUG ,
]);
        
$daemon->run();