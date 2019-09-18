<?php

namespace Apolinux\PlatformTools\Process;

/**
 * Description of 
 *
 * @author drake
 */
class RunFork implements Runnable{
    
    private $runner ;
    
    public function __construct(Runnable $runner) {
        $this->runner = $runner ;
    }
    
    public function run(){
        $pid = pcntl_fork();
        
        if($pid == 0){
            $this->runner->setStatus(Logger::IS_GRANSON) ;
            // in grandson
            $this->runner->run() ;
            exit(0) ;
        }
        //wait child process ends
        while (1) {
            $res = pcntl_waitpid(0, $status, WNOHANG) ;
            //@TODO if children number is more than one, change process
            // @see https://www.php.net/manual/en/function.pcntl-waitpid.php#115714
            if($res == -1 || $res > 0){
                Logger::log(__METHOD__ .'. child exited with status:'. pcntl_wexitstatus($status), 
                        Logger::MODE_DEBUG) ;
                break ;
            }
            if(! Daemon::mustRun()){
                // kill child
                posix_kill($pid, SIGTERM) ;
                Logger::log(__METHOD__ .'. sent SIGTERM to child', Logger::MODE_DEBUG) ;
            }
            sleep(1) ;
        }
    }
}
