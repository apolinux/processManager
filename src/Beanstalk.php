<?php

namespace ProcessManager;

use Pheanstalk\Pheanstalk;

/**
 * Description of Beanstalk
 *
 * @author drake
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
    private $options ;
    
    public function __construct($options){
        $this->checkValidOptions($options);
    }
    
    private function checkValidOptions($options){
        $valid = ['host'];
        //$total = $valid + ['timeout'] ;
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
    
    public function setTube($tube){
        $this->options['tube'] = $tube ;
    }
    
    /**
     * 
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
    
    public function sendMsg($msg){
        $this->connect()
             ->useTube($this->options['tube'])
             ->put($msg) ;
    }
    
    public function readMsg(){
        $conn = $this->connect()
             ->watch($this->options['tube']);
        if($this->options['tube'] != self::DEFAULT_TUBE){
             $conn->ignore(self::DEFAULT_TUBE) ;
        }
        $job = $conn->reserve();
        $data = $job->getData();
        $conn->delete($job);
        return $data ;
    }
    
    public function clearTube(){
        do{
            $job = $this->connect()->watch($this->options['tube'])->reserveWithTimeout(0);
            if(! $job){
                break ;
            }
            $this->connect()->bury($job);
        }while(1) ;
    }
    
    public function clearConn(){
        $this->connector = null ;
    }
}
