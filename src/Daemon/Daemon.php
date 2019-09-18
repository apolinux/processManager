<?php

namespace Apolinux\PlatformTools\Daemon;
use Apolinux\PlatformTools\Process\Logger;

/**
 * manage creation and control of daemon processes using fork
 *
 * @author Carlos Arce
 */
class Daemon {
    
    /**
     * file where process id pid is saved
     * @var string
     */
    private $pid_file ;
    
    /**
     *  logging directory
     * @var string 
     */
    private $log_dir ;
    
    /**
     * task to run
     * @var callable
     */
    //private $task ;
    
    /**
     * name of process
     * @var string
     */
    private $name ;
    
    /**
     * mode of running task: on infinite loop or run once
     * @var int
     */
    //private $task_mode ;
    
    /**
     * time to wait on each loop in loop mode
     * @var int
     */
    //private $wait_loop_task_time = 2 ;
    
    /**
     * time to wait before finish stopping process
     * @var int
     */
    private $timeout_after_kill = 15 ;
    
    /**
     * time to wait after start process
     * @var int
     */
    private $timeout_after_start = 2 ;

    /**
     * stop loop if there is an exception
     * @var bool
     */
    //private $stop_on_exceptions = false ;
    
    /**
     * time to wait between stop and start in restart command
     * @var int
     */
    private $wait_restart_time = 2 ;
    
    /**
     * determines if current process is parent, child or granson
     * @var int
     */
    protected $ground_status = Logger::IS_PARENT ;
    
    private $log_mode = Logger::MODE_WARNING ;
    
    private $main_task ;
    
    private $main_loop_task ;
    
    private $tasks = [] ;
    
    /**
     * instance of this class
     * @var Daemon
     */
    private static $instance ;

    /**
     * define if task must run or be stopped
     * @var bool
     */
    private static $must_run ;    
    
    public function __construct($config){
        $this->init($config);
    }
    /**
     * init configuration
     * 
     * @param array $config
     */
    private function init(array $config){
        $param_valid = ['pid_file', 'log_dir', 'name' /*,'task_mode'*/] ;
        foreach($param_valid as $param){
            if(!array_key_exists($param, $config)){
                $this->die("The param '$param' is not set");
            }
            $this->$param = $config[$param] ;
        }
        
        $param_opt = [/*'task', 'wait_loop_task_time',*/'timeout_after_kill','timeout_after_start',
          /*'stop_on_exceptions',*/'wait_restart_time',/*'run_path', 'run_path_args', 'run_path_env',
          'php_bin',*/'log_mode'];
        foreach($param_opt as $param){
            if(!array_key_exists($param, $config)){
                continue ;
            }
            $this->$param = $config[$param] ;
        }
    }
    
    /**
     * runs daemon 
     * 
     * read command from cli and takes action
     * valid actions are : start, stop, restart, status, and help
     */
    public function run(){
        $this->checkBasic();
        // check input parameters to decide action to take
        $action = $this->getActionFromCli();
        
        Logger::setMode($this->getConfig('log_mode')) ;
        
        switch($action){
            case 'start' : 
                $this->start() ;
                break ;
                
            case 'stop' :
                $this->stop() ;
                break ;
            
            case 'restart' :
                $this->restart() ;
                break ;
                
            case 'status' :
                $this->status() ;
                break ;
                
            case 'fg' :
                $this->checkIfStarted();
                $this->runTaskList() ;
                break ;    
                
            case 'help' : 
            default:
                $this->info() ;
        }
    }
    
    private function checkBasic(){
        if (PHP_SAPI !== 'cli') {
            $this->die('Must run in CLI mode');
        }
        
        $this->checkBasicTask();
    }
    
    private function checkBasicTask(){
        foreach($this->tasks as $task){
            if($task->type == 'method'){
                if(! is_callable($task->task)){
                    $this->die("The task '$task->task' is not a callable");
                }
            }elseif($task->type == 'command'){
                if(! file_exists($task->command)) {
                    $this->die("The file '$task->command' does not exists") ;
                }
                if( ! is_executable($task->command)){
                    $this->die("The file '$task->command' is not executable");
                }
            }
        /*if( in_array($this->getConfig('task_mode') ,[ 
                                        TaskManager::MODE_ONCE_CMD ,
                                        TaskManager::MODE_ONCE_CMD_FORK ,
                                        TaskManager::MODE_LOOP_CMD_FORK ,
                                       ] , true)){
            
            $run_path= $this->getConfig('run_path');
            if(empty($run_path)){
                $this->die('The run path of executable is not defined') ;
            } 
            if(! file_exists($run_path)){
                $this->die("The file '$run_path' does not exists") ;
            }
            $php_bin = $this->getConfig('php_bin');
            if(! file_exists($php_bin)){
                $this->die("The PHP Binary '$php_bin' does not exists") ;
            }
            
            if(!is_executable($php_bin)){
                $this->die("The PHP Binary '$php_bin' is not executable") ;
            }
        }else{
            if(empty($this->task)){
                $this->die('The task is not defined') ;
            }
        }
         */
        }
    }
    
    /**
     * shows help
     */
    private function info(){
        $progname = basename($_SERVER['argv'][0]) ;
        $out = <<< END
$this->name Daemon
Runs a process in background and controlls it
Usage: $progname  start|stop|restart|status|help|h
  start  : starts the daemon
  stop   : stops the daemon
  restart: stop, then restart daemon
  status : shows daemon info if it is running
  fg     : start in foreground, no daemon                
  help,h : shows this help
END;
        echo "$out\n" ;
    }
    
    
    /**
     * show process status
     */
    private function status(){
        if($this->pidFileExists() && $this->processIsRunning()){
            echo "process is running with pid: " . $this->getPidChild() ."\n" ;
        }else{
            echo "process is not running\n" ;
        }
    }
    
    /**
     * read parameter from command line
     */
    private function getActionFromCli(){
        $parameter = $_SERVER['argv'][1] ?? null ;
        if($parameter == null or in_array(str_replace('-','',$parameter), ['h','help'],true)){
            $parameter = 'help' ;
        }elseif(! in_array($parameter, ['start','stop','restart','status','fg']) ){
            $parameter = 'help' ;
        }
        return $parameter ;
    }
    
    /**
     * starts the daemon with task
     * 
     * @throws \Exception
     */
    private function start(){
        umask(0) ; // chmod guo+rwx
        // check if started
        $this->checkIfStarted();
        
        $this->log('Starting Daemon') ;
        $pid = pcntl_fork();
        if($pid == 0){
            $this->ground_status = Logger::IS_CHILD ;
            $this->assignSignals();
            // inside child
            // Closes an open file descriptors system STDIN, STDOUT, STDERR
            fclose(STDIN);   
            fclose(STDOUT);
            fclose(STDERR);
            //$logDir = __DIR__ ;
            // redirect stdin to /dev/null
            $STDIN = fopen('/dev/null', 'r'); 
            // redirect stdout to a log file
            $STDOUT = fopen($this->getStdoutFile(), 'ab');
            // redirect stderr to a log file
            $STDERR = fopen($this->getStderrFile(), 'ab');
            
            $sid = posix_setsid();
            if ($sid < 0) {
                $this->die("Can't set session leader") ;
            }
            
            chdir('/'); 
            cli_set_process_title($this->name) ;
            // call user process
            $this->runTaskList();
            
            exit(0);
        }elseif($pid == -1){
            $this->die('can not do forking');
        }
        
        $this->saveChildPid($pid);
        
        // wait and check if process is running
        sleep($this->timeout_after_start) ;
        if(! $this->processIsRunning()){
            $this->log('Daemon started and finished soon. last log lines:' .
                    "\n" . $this->readLastLogLines(10)) ;
            exit(1);
        }
        
        $this->log('Daemon started') ;
    }

    /*private function runTask(){
        $task = new TaskManager($this) ;
        $task->run() ;
    }*/
    
    /**
     * get stdout file
     * @return string
     */
    private function getStdoutFile(){
        return $this->log_dir. '/'. $this->name .'.log' ;
    }
    
    /**
     * get stderr file
     * @return type
     */
    private function getStderrFile(){
        return $this->log_dir. '/'. $this->name .'.error.log' ;
    }
    
    /**
     * read n last lines from error and app log
     * 
     * @param int $lines
     * @return string
     */
    private function readLastLogLines($lines){
        $out = [];
        foreach([$this->getStderrFile(), $this->getStdoutFile()] as $file){
            $out[]= "--- ". $file . ' ---' ;
            $out[]=trim($this->tailFile($file,$lines));
        }
        return join("\n",$out) ;
    }
    
    /**
     * tails n lines from file
     * 
     * @param string $file
     * @param int $lines
     * @return string
     */
    private function tailFile($file, $lines){
        $f = fopen($file,'r');
        $out = [] ;
        $charcont=1 ;
        $line = '' ;
        $contline = 0 ;
        do{
            $ret = fseek($f,-$charcont,SEEK_END);
            if($ret == -1 ){
                break ;
            }
            $char = fgetc($f);
            $line = $char. $line ;
            $charcont++ ;
            if( in_array($char ,["\n","\r"],true) ){
                $contline++ ;
                array_unshift($out, trim($line));
                $line = '' ;
            }
        }while( ($contline <= $lines) );
        fclose($f);
       
        return join("\n",$out) ;
    }
    
    /**
     * signal handler
     * 
     * @param int $signo
     */
    public static function sigHandler($signo){
        Logger::log('sighandler, pid:'. posix_getpid().
                ', signal received:'. $signo .
                ', task mode:' . self::$instance->task_mode, 
                Logger::MODE_DEBUG
                );
        //error_log(__METHOD__.', in pid:'. posix_getpid().', signo:'. $signo);
        switch ($signo) {
            case SIGTERM:
               // actions SIGTERM signal processing
               switch(self::$instance->task_mode){
                   case TaskManager::MODE_LOOP_CALL:
                   case TaskManager::MODE_LOOP_CALL_FORK:
                   case TaskManager::MODE_LOOP_CMD_FORK :    
                       self::$must_run = false ;
                   break ;    
                   case TaskManager::MODE_ONCE_CALL:
                   case TaskManager::MODE_ONCE_CMD:
                       self::$instance->removePidFile() ;
                       self::$instance->log('Daemon terminated by signal') ;
                       exit(0);
               }
            break;
            case SIGHUP:
                // reread the configuration file and initialize the data again
            break;
            default:
            // Other signals, information about errors
        }
    }
    
    /**
     * assign process signals
     * 
     * only works for task processes
     */
    private function assignSignals(){
        // from 7.1+ , instead using declare('ticks=1');
        pcntl_async_signals(true);
        
        self::$instance = $this ;
        pcntl_signal(SIGTERM, [__CLASS__ , "sigHandler"]);
        pcntl_signal(SIGHUP,  [__CLASS__ , "sigHandler"]);
    }
    
    /**
     * check if daemon has started
     */
    private function checkIfStarted(){
        if($this->pidFileExists() && $this->processIsRunning()){
            $this->die("process is running with pid: " . $this->getPidChild()) ;
        }
    }
    
    /**
     * stop process
     */
    private function stop(){
        // verify already if its running
        if(! ( $pid = $this->getPidChild($die_if_error=false)) ){
            $this->die('The Daemon is not running') ;
        }
        
        $this->log('Stopping daemon');
        
        //$pid = $this->getPidChild();
        
        posix_kill($pid, SIGTERM);
        
        $start_time = time() ;
        do{
            // check if is running
            $process_running = $this->processIsRunning($pid) ;
            $diff_time = time() - $start_time ;
        }while( ($diff_time < $this->timeout_after_kill) && $process_running ) ;
        
        if($process_running){
            $this->die('Can\'t stop daemon, possibly insufficient permissions') ;
        }
        $this->removePidFile();
        
        $this->log('Daemon stopped') ;
    }
    
    /**
     * restart daemon
     */
    private function restart(){
        if(! ( $pid = $this->getPidChild($die_if_error=false)) ){
            $this->log('The Daemon is not running') ;
        }else{
            $this->stop() ;
        }
        sleep($this->wait_restart_time);
        $this->start() ;
    }
    
    /**
     * check if process is running 
     * @param int $pid
     * @return boolean
     */
    private function processIsRunning($pid=false){
        if($pid){
            return file_exists('/proc/' . $pid) ;
        }
        if(! $this->pidFileExists()){
            return false ;
        }
        return file_exists('/proc/' . $this->getPidChild()) ;
        
    }
    
    /**
     * prints a log
     * @param string $msg
     */
    protected function  log($msg){
        return Logger::log($msg, $this->ground_status) ;
    }
    
    /**
     * terminates process
     * @param string $msg
     */
    protected function die($msg){
        $this->log($msg) ;
        exit(1);
    }
    
    /**
     * save child to file
     * @param int $pid
     */
    private function saveChildPid($pid){
        $r = file_put_contents($this->pid_file, $pid) ;
        if(false === $r){
            $this->die("Can't write pid to file $this->pid_file") ;
        }
    }
    
    /**
     * check if pid file exists
     * @return bool
     */
    private function pidFileExists(){
        return file_exists($this->pid_file);
    }
    
    /**
     * get pid for background process
     * @param bool $die_if_error
     * @return boolean
     * @throws DaemonException
     */
    private function getPidChild($die_if_error=true){
        try{
            if(! $this->pidFileExists()){
                throw new DaemonException("The pid file $this->pid_file does not exists") ;
            }

            if(! is_readable($this->pid_file)){
                throw new DaemonException("The pid file $this->pid_file can't be read") ;
            }

            $pid = file_get_contents($this->pid_file) ;
            if($pid == "" or (int)$pid < 1){
                throw new DaemonException("The pid $pid is not valid") ;
            }
            
            return (int)trim($pid) ;
        }catch(DaemonException $e){
            if( $die_if_error ){
                return $this->die($e->getMessage()) ;
            }
            return false ;
        }
    }
    
    /**
     * removes pid file
     */
    protected function removePidFile(){
        if(file_exists($this->pid_file)){
            unlink($this->pid_file) ;
        }
    }
    
    public function getTask(){
        return $this->task ;
    }
    
    public function getConfig($param){
        if(property_exists($this, $param)){
            return $this->$param ;
        }
        $this->die("The param '$param' is not valid");
    }
    
    public static function mustRun($must=null){
        if(is_null($must)){
            return self::$must_run ;
        }
        self::$must_run = $must ;
    }

    
    public function addTaskMethod($method, $params=[]){
        $this->tasks[] = (object)['type' => 'method' , 'task' => $method , 'params' => $params] ;
    }
    
    public function addTaskCallable($method, $params=[]){
        $this->tasks[] = (object)['type' => 'callable' , 'task' => $method , 'params' => $params] ;
    }
    
    public function addTaskCommand($command, $params = [], $env=[]){
        $this->tasks[] = (object)['type' => 'command' , 'command' => $command ,
                                  'params' => $params , 'env' => $env] ;
    }
    
    public function addMainTask(callable $callable){
        $this->main_task = $callable ;
    }
    
    public function addMainLoopTask(callable $callable){
        $this->main_loop_task = $callable ;
    }
    
    
    private function runTaskList(){
        if(count($this->tasks)< 1){
            $this->die('There are no task to daemonize') ;
        }
        // call task
        $children = [] ;
        foreach($this->tasks as &$task){
            $pid = pcntl_fork(); 
            if($pid == 0){
                $this->runTask($task);
                exit(0);
            }
            elseif($pid == -1){
                die('can\'t fork') ;
            }else{
                $task->pid = $pid ;
                $children[] = $pid ;
            }
        }
        
        ($this->main_task)();
        
        while(count($children) > 0) {
            foreach($children as $key => $pid) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                // If the process has already exited
                if($res == -1 || $res > 0){
                    unset($children[$key]);
                    echo "child with pid $pid exited with status:". pcntl_wexitstatus($status) ."\n" ;
                }
            }
            ($this->main_loop_task)();
            
            sleep(1);
        }
        
        // wait for children to end
        /*while (1) {
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
        }*/
    }
    
    private function runTask($task){
        if( ($task->type == 'method') || ($task->type == 'callable') ){
            call_user_func_array($task->task, $task->params) ;
        }elseif($task->type =='command'){
            pcntl_exec($task->command, $task->params, $task->env) ;
            die("The command $task->command not run");
        }
    }
}
