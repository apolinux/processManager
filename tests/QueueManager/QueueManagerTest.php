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

        $tmp_file = tempnam('/tmp', __CLASS__ );
        // consumer job
        /*$callable_task = function($queue, $cont){
           for($cont=1; $cont<=4; $cont++){
                $msg = $queue->readMsg();
                echo "in task $cont. msg: $msg\n" ;
           }
        };*/

        //$num_tasks= 3;
        $msg_pending = explode(" ","El sabio no dice todo lo que piensa, pero siempre piensa todo lo que dice") ;
        for($cont=1 ; $cont<= count($msg_pending); $cont++){
            $queuemngr->addTask( function ($queue) use ($tmp_file){
                $msg = $queue->readMsg() ;
                file_put_contents($tmp_file,((string)$msg) . "\n");
            } ,[]);
        }
        
        // in producer job, send info to children using queue
        $queuemngr->addTask(function($queuemngr) use ($msg_pending){
            foreach($msg_pending as $msg) {
                 $queuemngr->sendMsg($msg);
            }
        });
        // fork and run tasks 
        $queuemngr->run();
        
        // @TODO: need to test if task are running in bg? is it possible?
        
        $this->assertEquals(count($msg_pending),count(file($tmp_file)));
        unlink($tmp_file);
    }
}
