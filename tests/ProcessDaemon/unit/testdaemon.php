#!/bin/env php -d display_errors=1
<?php
use ProcessManager\ProcessDaemon\Daemon;
//use ProcessManager\ProcessDaemon\TaskManager;

require __DIR__ .'/../../bootstrap.php' ;
require __DIR__ .'/../../../vendor/autoload.php' ;

$options = require_once __DIR__ . '/daemonopts.php' ;

function taskBasic(){
    echo "start task\n" ;
    $f = tmpfile();
    for($i=1 ; $i<=40000; $i++){
       fwrite($f, md5(base64_decode(random_bytes(256)))) ;
    }
    echo "file created\n" ;
    fseek($f,0) ;
    while(! feof($f)){
        $null = fgetc($f);
        unset($null);
    }
    fclose($f);
    echo "file closed\n" ;
}

function testTask(){
    return taskBasic();
}

function testTaskException(){
    taskBasic();
    throw new RunTimeException('Custom error on task');
}

function testTaskFail(){
    taskBasic();
    blablabla('nothing');
}

function testTaskOnce(){
    while(1){
        taskBasic();
    }
}

function testTaskCallForkLoop(){
    return taskBasic();
}

$daemon = new Daemon($options) ;
$daemon->run();
