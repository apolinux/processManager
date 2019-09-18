<?php

/**
 * @author drake
 */
// TODO: check include path
ini_set('include_path', ini_get('include_path').  
        PATH_SEPARATOR . __DIR__ .'/../' .
        PATH_SEPARATOR.dirname(__FILE__).'/../../../phpunit-4.8');

require_once __DIR__ .'/../vendor/autoload.php' ;

spl_autoload_register(function($class){
    $class2 = str_replace('\\','/',$class) ;
	if( stream_resolve_include_path($class .'.php')){ 
		require_once $class .'.php' ;
	}elseif(stream_resolve_include_path($class2.'.php')){
        require_once $class2 .'.php' ;
    }
});
