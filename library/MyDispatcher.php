<?php

use Phalcon\Mvc\Dispatcher;

class MyDispatcher extends Dispatcher {
  
  public function getHandlerClass(){
  
    if( version_compare(phpversion(), '7.0', '<') )	
      return parent::getHandlerClass();
    
    parent::getHandlerClass();
    return ucfirst($this->_handlerName) . $this->_handlerSuffix;
  }
}