# processManager
Process Manager written in PHP

Controls a pool of subprocesses and send them messages using queue. This first version uses beanstalk to communicate processes.

There is a main loop in parent that sends data to children through the queue. The children processes read this data from queue and process it. 

The main loop checks if there are died children and recreate them if it's necessary.

# Example

example of use:

    <?php

    $beanstalk = new beanstalkConn($host, $port);

    $beanstalk->defineTube("cola_mt_3dm_platx");

    $queuemngr = new QueueMngr($beanstak)

    for($cont=1 ; $cont<=$num_tasks; $cont++){
        $queue->addTask( $callable_task , [$queuemngr] );
    }

    // fork and run tasks 
    $queuemngr->run();

    $callable_task = function($queue){
       $msg = $queue->read();
       // process queue 
       // ...
    }

    // in parent process, send info to children using queue
    for($mtpending as $mt) {
	     $queue->sendMsg(["mt" => $mt]);
    }

