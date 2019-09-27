<?php

use PHPUnit\Framework\TestCase;
/**
 * Description of DaemonTestCase
 *
 * @author drake
 */
class DaemonTestCase extends TestCase{
    protected $proc_name ;
    protected $pid_file ;
    protected $options ;
    protected $dir_var ;
    
    public function tearDown() {
        if(file_exists($this->pid_file)){
            $pid = (int)trim(file_get_contents($this->pid_file));
            if($pid > 0){
                posix_kill($pid,SIGTERM) ;
            }
        }
        if(! empty($this->dir_var)){
            foreach(glob("$this->dir_var/*.*") as $file){
                unlink($file);
            }
        }
    }
    
    protected function setOptions($options){
        $optionsf = __DIR__ .'/daemonopts.php' ;
        file_put_contents($optionsf, "<?php\n return ". var_export($options,true) . ";") ;
    }
    
    protected function runDaemon($action,$msg_expected='',$return_code=0) {
        set_time_limit(5); 
        $daemont = __DIR__ . '/testdaemon.php';
        $this->assertFileExists($daemont,'The script daemontest must exists') ;
        
        $cmd = "php -d display_errors=1 $daemont $action";
        
        exec("$cmd 2>&1",$output, $return);
        $this->assertEquals($return_code, $return ,
                "Error running command '$cmd'. return: $return. out: ". join("\n", $output));
        
        if($msg_expected != ''){
            $last_line = array_pop($output);
            $this->assertRegexp("/$msg_expected/",$last_line,"The output not match. cmd:\n".
                    "'$cmd'" .". full output:\n".
                    join("\n",$output)) ;
        }
    }
}
