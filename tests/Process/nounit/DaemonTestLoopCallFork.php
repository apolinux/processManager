<?php
use Apolinux\PlatformTools\Process\Daemon;

require __DIR__ .'/../bootstrap.php' ;
require __DIR__ .'/../../vendor/autoload.php' ;
        
$daemon = new Daemon([
  'pid_file' => __DIR__ .'/testDaemonForkWait.pid' ,
  'log_dir' => __DIR__  ,
  'name' => 'testDaemonForkWait' ,
  'task_mode' => Apolinux\PlatformTools\Process\TaskManager::MODE_LOOP_CALL_FORK,
  'task' => 'testTask' ,
  'wait_loop_task_time' => 0 ,
  'timeout_after_kill' => 15 ,
  'timeout_after_start' => 1 ,
  'stop_on_exceptions' => false ,
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
}

$daemon->run();