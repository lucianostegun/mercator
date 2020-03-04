<?php

use Phalcon\Queue\Beanstalk;

class MyBeanstalk extends Beanstalk {

  const PRIORITY_URGENT = 0;
  const PRIORITY_HIGH   = 1;
  const PRIORITY_NORMAL = 2;
  const PRIORITY_LOW    = 3;
  
  public static function getPriorityList(){
    
    return array(
      self::PRIORITY_URGENT => 'Urgent',
      self::PRIORITY_HIGH   => 'High',
      self::PRIORITY_NORMAL => 'Normal',
      self::PRIORITY_LOW    => 'Low',
    );
  }
  
  public static function getOptionsForSelectPriority($default='', $returnArray=false, $firstOption='All'){
    
    $optionList  = array(''=>'All');
    $optionList += self::getPriorityList();

    if( $returnArray === 'ajax' )
      return optionsForSelectAjax($optionList, $default);
      
    if( $returnArray )
      return $optionList;
    
    return optionsForSelect($optionList, $default);
  }
}