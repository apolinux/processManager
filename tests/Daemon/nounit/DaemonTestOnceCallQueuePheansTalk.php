<?php
use Apolinux\PlatformTools\Daemon\Daemon;
use Pheanstalk\Pheanstalk;

require __DIR__ .'/../../bootstrap.php' ;
require __DIR__ .'/../../../vendor/autoload.php' ;

$pheanstalk = Pheanstalk::create('127.0.0.1');

$procname = substr(basename(__FILE__),0,-4) ;
$daemon = new Daemon([
  'pid_file' => __DIR__ ."/../../'. $procname.pid" ,
  'log_dir' => __DIR__  . '/../../var' ,
  'name' => $procname ,
  /*'task_mode' => Apolinux\PlatformTools\Process\TaskManager::MODE_ONCE_CALL ,
  'task' => 'testTask'*/
]);
do{
    //echo "aca1";
    $job = $pheanstalk->watch('testtube')->ignore('default')->reserveWithTimeout(0);
    if($job){
        $pheanstalk->delete($job);
    }
    //var_dump($job);
    //echo "aca2";
}while($job);
/*$pheanstalk->useTube('testtube');
$pheanstalk->put('bla');*/
$pheanstalk = Pheanstalk::create('127.0.0.1');


$x = function ($fork, $max=3) use ($pheanstalk){
    /*$cont=0 ;
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
    }*/
    $jobcount=0;
    while(1){
        $job = $pheanstalk
        ->watch('testtube')
        ->ignore('default')
        ->reserve();
        $response = $job->getData();
        if($response=='down'){
            $pheanstalk->delete($job);
            echo "fork $fork terminado, jobs: $jobcount\n" ;
            break ;
        }
        $jobcount++ ;
        echo "En el fork $fork: $response\n";

        $pheanstalk->delete($job);
        
    }
};

//$daemon->addTaskMethod('testTask',[1, 4]);
for($cont=1 ; $cont<=10 ; $cont++){
    $daemon->addTaskCallable($x,[$cont, 4]);
}
//$daemon->addTaskCallable($x,[2, 4]);

//$daemon->addTaskCommand('/usr/bin/php', [ __DIR__ . '/test_task.php', 'bla=1'], ['base' => 'fut']);

$daemon->addMainTask(function() use ($pheanstalk){
    $pheanstalk->useTube('testtube');
    for($cont=1 ; $cont<=50; $cont++){
        $pheanstalk->put("los caballos de roma no van a china cont:$cont. id:" . md5(uniqid()));
    }
    for($cont=1 ; $cont<=10 ; $cont++){
        $pheanstalk->put('down') ;
    }
});

$daemon->addMainLoopTask(function(){
    //echo "inside loop\n" ;
});


        
$daemon->run();


    
