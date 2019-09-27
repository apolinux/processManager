<?php
use ProcessManager\ProcessDaemon\Daemon;
use ProcessManager\ProcessDaemon\TaskManager;

require __DIR__ .'/../../bootstrap.php' ;
require __DIR__ .'/../../../vendor/autoload.php' ;
        
$daemon = new Daemon([
  'pid_file' => __DIR__ .'/testDaemonForkWaitFail.pid' ,
  'log_dir' => __DIR__  ,
  'name' => 'testDaemonForkWaitFail' ,
  'task_mode' => TaskManager::MODE_LOOP_CALL_FORK,
  'task' => 'testTask' ,
  'wait_loop_task_time' => 0 ,
  'timeout_after_kill' => 5 ,
  'timeout_after_start' => 1 ,
  //'stop_on_exceptions' => false ,
]);
        
function testTask(){
    echo "start task\n" ;
    $f = tmpfile();
    for($i=1 ; $i<=100000; $i++){
       fwrite($f, md5(base64_decode(random_bytes(256)))) ;
    }
    echo "file created\n" ;
    fseek($f,0) ;
    while(! feof($f)){
        $null = fgetc($f);
        unset($null);
    }
    fclose($f);
    echo "file closed\n" ;
    var_tonta(); // FATAL ERROR
}

$daemon->run();