<?php

namespace ProcessManager\ProcessDaemon;

/**
 * Description of Runner
 *
 * @author drake
 */
interface Runnable{
    
    //public function setStatus($status);
    
    public function run();
}
