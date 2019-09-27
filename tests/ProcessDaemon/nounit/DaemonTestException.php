<?php
use ProcessManager\ProcessDaemon\Daemon;
use ProcessManager\ProcessDaemon\TaskManager;

require __DIR__ .'/../../bootstrap.php' ;
require __DIR__ .'/../../../vendor/autoload.php' ;
        
$daemon = new Daemon([
  'pid_file' => __DIR__ .'/testDaemonException.pid' ,
  'log_dir' => __DIR__  ,
  'name' => 'testDaemonException' ,
  'task_mode' => TaskManager::MODE_LOOP_CALL ,
  'task' => 'testTask',
  'stop_on_exceptions' => true ,
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
    throw new RunTimeException('Custom error on task');
}

$daemon->run();