<?php

namespace ProcessManager;

/**
 * Description of Queueable
 *
 * @author drake
 */
interface Queueable {
    public function sendMsg($msg);
    
    public function readMsg();
}
