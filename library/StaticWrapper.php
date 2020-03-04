<?php

class StaticWrapper {
  
  public function __call($name, $arguments){
    
    return call_user_func_array($name, $arguments);
  }

  public function execute($className, $methodName, $arguments=array()){
    return call_user_func_array(array($className, $methodName), $arguments);
  }
}