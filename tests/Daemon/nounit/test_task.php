<?php

function testTask($fork, $max=3){
    $cont=0 ;
    while($cont++<=$max){
    echo "start task $fork\n" ;
    $f = tmpfile();
    for($i=1 ; $i<=100000; $i++){
       fwrite($f, md5(base64_decode(random_bytes(256)))) ;
    }
    echo "file created $fork\n" ;
    fseek($f,0) ;
    while(! feof($f)){
        $null = fgetc($f);
        unset($null);
    }
    fclose($f);
    echo "file closed $fork\n" ;
    }
}

testTask(0, 2) ;
