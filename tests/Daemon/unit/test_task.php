#!/usr/bin/php
<?php
pcntl_signal(SIGTERM, SIG_DFL);
pcntl_signal(SIGHUP, SIG_DFL);

function testTask(){
    while(1){
    echo "start task\n" ;
    $f = tmpfile();
    for($i=1 ; $i<=30000; $i++){
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
}

testTask();
