<?php

use ProcessManager\QueueManager\Beanstalk;
use ProcessManager\QueueManager\QueueManager;

require_once __DIR__ . '/../../vendor/autoload.php' ;
//require __DIR__ .'/../bootstrap.php' ;

$beanstalk = new Beanstalk(['host' => 'localhost']);

$beanstalk->setTube("send-msgs");

$beanstalk->clearTube();
$beanstalk->clearConn();

$queuemngr = new QueueManager($beanstalk,[
  'restart_ended_task' => true ,
]) ;

// consumer job
$callable_task = function($queue, $taskid){
   for($cont=1; $cont<=4; $cont++){
        $msg = $queue->readMsg();
        echo "in task $taskid. msg: $msg\n" ;
   }
};

$num_tasks= 2;

for($cont=1 ; $cont<=$num_tasks; $cont++){
    $queuemngr->addTask( $callable_task , [$cont] );
}

$lock_file = '/tmp/queuemngrr.lock' ;
unlink($lock_file);

$queuemngr->addTask( function ($queue, $taskid) use($lock_file){
    
    if(! file_exists($lock_file)){
        touch($lock_file);
        throw new Exception('Exception first time') ;
    }
    
    for($cont=1; $cont<=4; $cont++){
        $msg = $queue->readMsg();
        echo "in task $taskid. msg: $msg\n" ;
   }
    
}, [3] );


// in producer job, send info to children using queue
$queuemngr->addTask(function($queuemngr){
    $msg_pending = explode(" ","El amor es como la vida y la naturaleza lo es todo") ;
    
    foreach($msg_pending as $msg) {
         $queuemngr->sendMsg($msg);
    }
});
// fork and run tasks 
$queuemngr->run();
