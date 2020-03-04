<?php

use Phalcon\Cli\Task;

class TaskBase extends Task {
  
  private $enableDebug = DEBUG;
  
  public function initialize(){

//		system('clear'); // Limpa a tela quando executamos a chamada da tarefa
    $this->debug('INITIALIZING', 0);
    
    $this->taskName   = $this->dispatcher->getTaskName();
    $this->actionName = $this->dispatcher->getActionName();
    
    $this->view = new stdClass();
    $this->view->mode       = 'json';
    $this->view->started_at = date('Y-m-d H:i:s');
    $this->view->status     = null;
    $this->view->message    = null;
    $this->view->data       = array();
    
//    try {
//      $this->debug('CONNECTING TO DATABASE...', 0, false, null);
//      $this->db->getConnectionId();
//      $this->debug(sprintf(' [ %s ]', $this->coloredString('OK', 'green')), 0, true);
//    }catch (PdoException $e) {
//      
//      $this->debug(sprintf('%s: %s', $this->coloredString('FAILURE', 'light_red'), $this->coloredString($e->getMessage(), 'yellow')), 0);
//      $this->view->status  = 'failure';
//      $this->view->message = 'Database connection error';
//      $this->afterExecuteRoute();
//      exit;
//    }
  }
  
  private function getPidLockFilePath($proccessFile){
    
    $proccessFile = nvl($proccessFile, "{$this->taskName}-{$this->actionName}.pid");
    
    return $this->util->getFilePath("../cache/$proccessFile");
  }
  
  protected function checkExclusiveRun($proccessFile=null){
    
    $pidFile = $this->getPidLockFilePath($proccessFile);
    $this->debug("PID file: $pidFile");
    
    if( !file_exists($pidFile) ){
      
      $this->createPidLock($proccessFile);
      return true;
    }
    
    $pid = trim(file_get_contents($pidFile));
    
    if( $pid != getmypid() ){
      
      // Verificando se o processo ainda está rodando
      if( posix_getpgid($pid) )
        throw new WorkerException('Another instance of this task is still running on PID ' . $this->coloredString('#' . $pid, 'yellow'), $pid);
      else
        $this->createPidLock($proccessFile);
    }
  }
  
  private function createPidLock($proccessFile=null){
    
    $pidFile = $this->getPidLockFilePath($proccessFile);
    $this->debug("Creating PID file: $pidFile");
    file_put_contents($pidFile, getmypid());
    
    return file_exists($pidFile);
  }
  
  public function afterExecuteRoute(){

    $this->debug('FINISHING PROCESS', 0);
    
    $result = array();

    if( isset($this->view->status) )
      $result['status'] = $this->view->status;

    if( isset($this->view->started_at) ){
      
      $result['started_at']  = $this->view->started_at;
      $result['finished_at'] = date('Y-m-d H:i:s');;
    }
    
    if( isset($this->view->message) )
      $result['message'] = $this->view->message;

    if( isset($this->view->data) )
      $result = array_merge($result, $this->view->data);
    
    if( $this->view->mode == 'json' ){
      
      $cpu = sys_getloadavg();
      
      if( STARTED_AT >= strtotime('-1 minute') )
        $cpu = $cpu[0];
      elseif( STARTED_AT >= strtotime('-5 minutes') )
        $cpu = $cpu[1];
      else
        $cpu = $cpu[2];
      
      $result['memory']      = (memory_get_usage(true)/1024/1024) . 'MB';
      $result['memory_peak'] = (memory_get_peak_usage(true)/1024/1024) . 'MB';
      $result['cpu']         = $cpu;
      echo json_encode((object)$result) . PHP_EOL;
    }elseif( $this->view->mode == 'plain' ){
    
      $maxKeyLength = 0;
      
      foreach ($this->view->data as $key=>$value)
        if( ($length = strlen($key)) > $maxKeyLength )
          $maxKeyLength = $length;

      foreach ($this->view->data as $key=>$value)
        echo sprintf('%- '.$maxKeyLength.'s : %s%s', $key, $value, PHP_EOL);

      echo $this->view->message;
    }
    
    $pidFile = $this->util->getFilePath("../cache/{$this->taskName}_{$this->actionName}.pid");
    
    if( file_exists($pidFile) )
      unlink($pidFile);
  }
  
  public function debug($message, $level=1, $force=false, $newLine=PHP_EOL){
    
    if( !$this->enableDebug && !$force )
      return;
    
    if (!$force) {
      echo sprintf('DEBUG #%s: %s | ', DEBUG_ID, date('Y-m-d H:i:s'));
    }
    
    echo sprintf('%s%s%s', str_repeat('  ', $level), $message, $newLine);
  }
  
  public function mainAction(array $params){
    
    $this->view->mode    = 'plain';
    $this->view->message = 'There is no main action configured for the task ' . $this->coloredString($this->taskName, 'green') . PHP_EOL;
  }
  
  public function coloredString($string, $foregroundColor){

    $foregroundColorList['black']        = '0;30';
    $foregroundColorList['dark_gray']    = '1;30';
    $foregroundColorList['blue']         = '0;34';
    $foregroundColorList['light_blue']   = '1;34';
    $foregroundColorList['green']        = '0;32';
    $foregroundColorList['light_green']  = '1;32';
    $foregroundColorList['cyan']         = '0;36';
    $foregroundColorList['light_cyan']   = '1;36';
    $foregroundColorList['red']          = '0;31';
    $foregroundColorList['light_red']    = '1;31';
    $foregroundColorList['purple']       = '0;35';
    $foregroundColorList['light_purple'] = '1;35';
    $foregroundColorList['brown']        = '0;33';
    $foregroundColorList['yellow']       = '1;33';
    $foregroundColorList['light_gray']   = '0;37';
    $foregroundColorList['white']        = '1;37';

    $coloredString = "\033[" . $foregroundColorList[$foregroundColor] . "m";

    return $coloredString . $string . "\033[0m";
  }
  
  public function getFileList($path, $recursive=false){

    $path = $this->util->getFilePath($path);
    
    $filePathList = array();
    foreach (glob($path) as $key=>$filePath) {
      $filePathList[] = $filePath;
    }
    
    return $filePathList;
  }
}