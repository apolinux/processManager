<?php
use Apolinux\PlatformTools\Daemon\Daemon;

require __DIR__ .'/../../bootstrap.php' ;
require __DIR__ .'/../../../vendor/autoload.php' ;
        
$procname = substr(basename(__FILE__),0,-4) ;
$daemon = new Daemon([
  'pid_file' => __DIR__ ."/../../'. $procname.pid" ,
  'log_dir' => __DIR__  . '/../../var' ,
  'name' => $procname ,
  /*'task_mode' => Apolinux\PlatformTools\Process\TaskManager::MODE_ONCE_CALL ,
  'task' => 'testTask'*/
]);
        
/*$task = new Task([
  'method' => 'testTask' ,
  'parameters' => [],
]);*/

$daemon->addTaskMethod('testTask',[1, 4]);
$daemon->addTaskCommand('/usr/bin/php', [ __DIR__ . '/test_task.php', 'bla=1'], ['base' => 'fut']);

function testTask($fork, $max=3){
    $cont=0 ;
    while($cont++<=$max){
    echo "start task $fork\n" ;
    $f = tmpfile();
    for($i=1 ; $i<=100000; $i++){
       fwrite($f, md5(base64_decode(random_bytes(256)))) ;
    }
    echo "file created $fork\n" ;
    fseek($f,0) ;
    while(! feof($f)){
        $null = fgetc($f);
        unset($null);
    }
    fclose($f);
    echo "file closed $fork\n" ;
    }
}
        
$daemon->run();