<?php

namespace ProcessManager;

use Pheanstalk\Pheanstalk;

/**
 * Pheanstalk wrapper used by QueueManager class
 * 
 * beanstalk is a simple queue manager 
 * pheanstalk is a PHP class that connect to beanstalk server and send/receive messages
 * 
 * @see https://github.com/pheanstalk/pheanstalk
 * @see https://beanstalkd.github.io/
 *
 * @author Carlos Arce <apolinux@gmail.com>
 */
class Beanstalk implements Queueable{
    
    const DEFAULT_PORT = 11300 ;
    const DEFAULT_CONNECT_TIMEOUT = 10 ;
    const DEFAULT_TUBE = 'default' ;
    
    /**
     *
     * @var Pheanstalk
     */
    private $connector ;
    
    /**
     *
     * @var array
     */
    private $options ;
    
    /**
     * 
     * @param array $options
     */
    public function __construct(array $options){
        $this->checkValidOptions($options);
    }
    
    /**
     * verify if options are valid
     * @param array $options
     * @throws \Exception
     */
    private function checkValidOptions($options){
        $valid = ['host'];
        foreach($valid as $option_valid){
            if(! isset($options[$option_valid])){
                throw new \Exception("Option $option_valid must be defined") ;
            }
            $this->options[$option_valid] = $options[$option_valid] ;
        }
        $this->options['port'] = $options['port'] ?? self::DEFAULT_PORT ;
        
        $this->options['connectTimeout'] = $options['connectTimeout'] ?? self::DEFAULT_CONNECT_TIMEOUT ;
        
        $this->options['tube'] = $options['tube'] ?? self::DEFAULT_TUBE ;
    }

    /**
     * define tube 
     * @param string $tube
     */
    public function setTube($tube){
        $this->options['tube'] = $tube ;
    }
    
    /**
     * connect to beanstalk server
     * @return Pheanstalk
     */
    public function connect(){
        if(! is_object($this->connector)){
            $this->connector = Pheanstalk::create(
                    $this->options['host'],
                    $this->options['port'] ,
                    $this->options['connectTimeout'] ,
                    ) ;
        }
        
        return $this->connector ;
    }
    
    /**
     * send message by tube
     * 
     * @param mixed $msg
     */
    public function sendMsg($msg, $priority= Pheanstalk::DEFAULT_PRIORITY ,
            $delay = Pheanstalk::DEFAULT_DELAY , $ttr = Pheanstalk::DEFAULT_TTR){
        $this->connect()
             ->useTube($this->options['tube'])
             ->put($msg, $priority, $delay, $ttr) ;
    }
    
    /**
     * reads a message from tube
     * 
     * reads message and delete
     * 
     * @return mixed
     */
    public function readMsg($timeout=false){
        $conn = $this->connect()
             ->watch($this->options['tube']);
        if($this->options['tube'] != self::DEFAULT_TUBE){
             $conn->ignore(self::DEFAULT_TUBE) ;
        }
        if($timeout === false){
            $job = $conn->reserve();
        }else{
            $job = $conn->reserveWithTimeout($timeout) ;
        }
        
        $data = $job->getData();
        $conn->delete($job);
        return $data ;
    }
    
    public function clearTube($bury=true){
        do{
            $job = $this->connect()->watch($this->options['tube'])->reserveWithTimeout(0);
            if(! $job){
                break ;
            }
            if($bury){
                $this->connect()->bury($job);
            }else{
                $this->connect()->delete($job);
            }
        }while(1) ;
    }
    
    public function clearConn(){
        $this->connector = null ;
    }
}
