<?php

class QueueManagerTest extends PHPUnit\Framework\TestCase{
    
    public function setUp() {
        parent::setUp();
    }
    
    public function testRun(){
        $beanstalk = new beanstalkConn('localhost', 'xxxx');

        $beanstalk->defineTube("cola_mt_3dm_platx");

        $queuemngr = new QueueMngr($beanstak) ;

        $callable_task = function($queue){
           $msg = $queue->readMsg();
           // process queue 
           // ...
        };

        $num_tasks= 3;
        
        for($cont=1 ; $cont<=$num_tasks; $cont++){
            $queuemngr->addTask( $callable_task , [$queuemngr] );
        }
        
        // in producer process, send info to children using queue
        $queuemngr->addTask(function() use ($queuemngr){
            foreach($mtpending as $mt) {
                 $queue->sendMsg(["mt" => $mt]);
            }
        });
        // fork and run tasks 
        $queuemngr->run();
        

        
    }
}
