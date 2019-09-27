<?php

namespace ProcessManager\ProcessDaemon;

/**
 * Description of Logger
 *
 * @author drake
 */
class Logger {
    
    const MODE_WARNING = 1 ;
    
    const MODE_DEBUG = 2 ;
    
    /**
     * define if current object is in parent process
     */
    const IS_PARENT = 0 ;
    
    /**
     * define if current object is in child process
     */
    const IS_CHILD = 1 ;
    
    /**
     * define if current object is in child of child process
     */
    const IS_GRANSON = 2 ;
    
    private static $mode = self::MODE_WARNING ;
    
    public static function setMode($mode){
        self::$mode = $mode ;
    }
    
    /**
     * prints a log
     * @param string $msg
     */
    public static function log($text, $ground_status='',$mode=null){
        if(empty($mode)){
            $mode = self::$mode ;
        }
        if($mode != self::$mode){
            return ;
        }
        $header = '';
        if($ground_status != self::IS_PARENT){
            $t = explode(' ',microtime());
            $micros = substr( sprintf("%.4f", $t[0]) , 2);
            $header = sprintf("[ %s.%s ] ", date('Y-m-d H:i:s') , $micros) ;
        }
        echo "{$header}{$text}\n" ;
    }
    
    
}
