<?php

use Phalcon\Db\Adapter\Pdo\Postgresql;

class MyPostgresql extends Phalcon\Db\Adapter\Pdo\Postgresql {

  private $logId = null;
  
  public function getLogId(){
    
    return $this->logId;
  }

  public function setLogId($logId){
    
    $this->logId = $logId;
  }
}