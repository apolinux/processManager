<?php

use ProcessManager\QueueManager\Beanstalk;
use ProcessManager\QueueManager\QueueManager;

class QueueManagerTest extends PHPUnit\Framework\TestCase{
    
    public function setUp() {
        parent::setUp();
        
    }
    
    public function testRun(){
        $beanstalk = new Beanstalk(['host' => 'localhost']);

        $beanstalk->setTube("send-msgs");

        $beanstalk->clearTube();
        $beanstalk->clearConn();

        $queuemngr = new QueueManager($beanstalk) ;

        // consumer job
        $callable_task = function($queue, $cont){
           for($cont=1; $cont<=4; $cont++){
                $msg = $queue->readMsg();
                echo "in task $cont. msg: $msg\n" ;
           }
        };

        $num_tasks= 3;

        for($cont=1 ; $cont<=$num_tasks; $cont++){
            $queuemngr->addTask( $callable_task , [$cont] );
        }

        // in producer job, send info to children using queue
        $queuemngr->addTask(function($queuemngr){
            $msg_pending = explode(" ","El sabio no dice todo lo que piensa, pero siempre piensa todo lo que dice") ;

            foreach($msg_pending as $msg) {
                 $queuemngr->sendMsg($msg);
            }
        });
        // fork and run tasks 
        $queuemngr->run();
        

        
    }
}
