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
]);
do{
    $job = $pheanstalk->watch('testtube')->ignore('default')->reserveWithTimeout(0);
    if($job){
        $pheanstalk->delete($job);
    }
}while($job);
$pheanstalk = Pheanstalk::create('127.0.0.1');


$x = function ($fork, $max=3) use ($pheanstalk){
    
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

for($cont=1 ; $cont<=10 ; $cont++){
    $daemon->addTaskCallable($x,[$cont, 4]);
}

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


    
