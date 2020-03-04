<?php
use Phalcon\Di;

class MyTools {

  public static function getDI(){
    
    return Di::getDefault();
  }

  public static function getAttribute($name, $key=null){
    
    if( !Di::getDefault()->has('session') )
      return null;
      
    $session = Di::getDefault()->get('session')->get($name);
    
    if( $key )
      return $session[$key];
    
    return $session;
  }

  public static function setAttribute($name, $value){
    
    return Di::getDefault()->get('session')->set($name, $value);
  }
}
