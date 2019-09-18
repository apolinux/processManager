<?php

namespace ProcessManager\QueueManager ;


/**
 * Description of Queueable
 *
 * @author drake
 */
interface Queueable {
    public function sendMsg($msg);
    
    public function readMsg();
}
