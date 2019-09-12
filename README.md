# processManager
Process Manager written in PHP

Controls a pool of subprocesses and send them messages using queue. This first version uses beanstalk to communicate processes.

There is a main loop in parent that sends data to children through the queue. The children processes read this data from queue and process it. 

The main loop checks if there are died children and recreate them if it's necessary.

# Example

example of use:

    <?php

    use ProcessManager\Beanstalk;
    use ProcessManager\QueueManager;

    require_once __DIR__ . '/../vendor/autoload.php' ;


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
        $msg_pending = explode(" ","El amor es como la vida y la naturaleza lo es todo") ;

        foreach($msg_pending as $msg) {
             $queuemngr->sendMsg($msg);
        }
    });
    // fork and run tasks 
    $queuemngr->run();

