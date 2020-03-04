<?php

//use Phalcon\Mvc\Model\Query\Builder;

class ModelBase extends \Phalcon\Mvc\Model {
  
  private   $_isNew           = true;
  private   $_modifiedColumns = array();
  protected $_primaryKeys     = array();
  protected $_originalValues  = array();
  protected $_skipLog         = false;
  
  public function initialize(){

    $this->useDynamicUpdate(true);
  }

  public function afterFetch(){
    
    $this->_isNew = false;
    $this->loadOriginalValues();
  }
  
  private function loadOriginalValues(){
    
    $fieldList = $this->metadata()[Phalcon\Mvc\Model\MetaData::MODELS_ATTRIBUTES];
    
    foreach ($fieldList as $fieldName) {
      
      $function = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldName)));
      $this->_originalValues[$fieldName] = $this->$function();
    }
  }
  
  public function getOriginalValues(){
    
    return $this->_originalValues;
  }
  
  public function getPublicCode(){
    
    return strrev(md5(get_class($this) . '-' . json_encode($this->getPrimaryKey(true)) . '-' . $this->getCreatedAt('Y-m-d H:i:s')));
  }
  
  public function getCode($hash=true){
    
    return sprintf('%s%05d', ($hash ? '#' : null), $this->getId());
  }
  
  protected function addModifiedColumn($column){
    
    if( !in_array($column, $this->_modifiedColumns) )
      $this->_modifiedColumns[] = $column;
  }
  
  public function getModifiedColumns(){
    
    return $this->_modifiedColumns;
  }
  
  public function isModifiedColumn($column){
    
    return in_array($column, $this->_modifiedColumns);
  }

  public function hasModifiedColumns($exceptUpdatedAt=false){
    
    if( $exceptUpdatedAt && count($this->_modifiedColumns) == 1 && $this->_modifiedColumns[0] == 'updated_at' )
      return false;

    return count($this->_modifiedColumns);
  }
  
  public function getPrimaryKey($values=false){
    
    if( $values ){
      
      $primaryKeys = array();
      foreach ($this->_primaryKeys as $primaryKey)
        $primaryKeys[] = $this->$primaryKey;
      
      return array_filter($primaryKeys);
    }
    
    return $this->_primaryKeys;
  }
  
  public function save($data = NULL, $whiteList = NULL){
  
    if( property_exists($this, 'updated_at') && $this->hasModifiedColumns(true) )
      $this->setUpdatedAt(date('Y-m-d H:i:s'));

    if( property_exists($this, 'created_at') && !$this->created_at )
      $this->setCreatedAt(date('Y-m-d H:i:s'));
    
    parent::save($data, $whiteList);
    
    if( !$this->_skipLog && $this->hasModifiedColumns(true) )
      Log::logSave($this);
    
    $this->_isNew = false;
    
    $this->_modifiedColumns = array();
  }
  
  public function delete(){
    
    if( property_exists($this, 'deleted') ){
      
      $this->setDeleted(true);
      $this->save();
    } else {
      
      parent::delete();
      
      if( !$this->_skipLog )
        Log::logDelete($this);
    }
  }
  
  public function restore($request=null){
    
    if( property_exists($this, 'deleted') ){
      
      $this->setDeleted(false);
      $this->save();
    }
  }
  
  public static function getOptionsForTagInput(){
    
    $options = array();
    foreach (self::getList() as $object) {
      
      $text = method_exists($object, 'toString') ? $object->toString() : $object->id;
      
      $option = array(
        'value' => $object->id, 
        'text'  => $text
      );
      
      $options[] = $option;
    }
    
    return $options;
  }
  
  public static function getOptionsForSelect($default='', $returnArray=false, $firstOption='All', $criteria=null, $orderBy=null, $options=array()){
  
    $firstOption = nvl($firstOption, 'All');
    
    $class      = get_called_class();
    $optionList = array(''=>$firstOption);
    
    if( method_exists($class, 'getOptionsForSelectSource') ){
      
      $objectList = $class::getOptionsForSelectSource($criteria, $orderBy, $options);
    } else {
      
      if( is_null($criteria) )
        $criteria = $class::query();

      if( property_exists($class, 'deleted') )
        $criteria->andWhere("$class.deleted = false");
  
      if( $orderBy )
        $criteria->orderBy($orderBy);
      
      $objectList = $criteria->execute();
    }
    
    foreach ($objectList as $object)
      if( method_exists($object, 'toString') )
        $optionList[$object->id] = $object->toString();
      else
        $optionList[$object->id] = $object->label;

    if( $returnArray === 'ajax' )
      return optionsForSelectAjax($optionList, $default);
      
    if( $returnArray )
      return $optionList;
    
    return optionsForSelect($optionList, $default);
  }
  
  public function toString(){
    
    $class = get_called_class();
    
    if( method_exists($this, 'getDescription') )
      return $this->getDescription();
    elseif( property_exists($this, 'description') )
      return $this->description;
    elseif( method_exists($this, 'getName') )
      return $this->getName();
    elseif( method_exists($this, ($func = 'get'.ucfirst($class).'Name')) )
      return $this->$func();
    elseif( property_exists($this, 'name') )
      return $this->name;
    elseif( property_exists($this, 'id') )
      return sprintf('#%05d', $this->id);
    
    return null;
  }
  
  public function formatDate($date, $format=null){

    if( !$date )
      return null;
    
    $date = trim(preg_replace('/[^0-9 \/\-:\.]/', '', $date));
    
    if( preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{2,4})( [0-9]+:[0-9]+(:[0-9]+)?)?$/', $date, $matches) ){
      
      $matches[] = ' 00:00:00';
      $timestamp = sprintf('%s-%s-%s%s', $matches[3], $matches[2], $matches[1], $matches[4]);
      $timestamp = strtotime($timestamp);
    }elseif( is_numeric($date) ){
      
      $timestamp = $date;
    } else {

      $timestamp = strtotime($date);
    }
    
    if( !$format )
      return $timestamp;
    
    return date($format, $timestamp);
  }
  
  public function checkOwnership($credential=null){
    
    $siteId    = MyTools::getAttribute('siteId');
    $accountId = MyTools::getAttribute('auth', 'id');
    $master    = MyTools::getAttribute('auth', 'master');
    
    if( $credential == 'administrator' )
      return true;
    
    if( $master )
      return true;
    
    if( property_exists($this, 'account_id') && ($this->getAccountId() == $accountId || $this->getAccountId() == null) )
      return true;

    if( property_exists($this, 'site_id') && ($this->getSiteId() != $siteId || $this->getSiteId() == null) )
      return false;
      
    return true;
  }
  
  public function jsonSerialize($restrict=array(), $extra=array(), $encode=false){
    
    $json = parent::jsonSerialize();
    
    $removeFieldList = array('created_at', 'updated_at', 'deleted', 'visible');
    
    foreach ($removeFieldList as $field)
      if( array_key_exists($field, $json) )
        unset($json[$field]);
    
    if( $restrict ){
      
      $restrict = array_fill_keys($restrict, null);
      $json = array_intersect_key($json, $restrict);
    }
    
    $json = array_merge($json, $extra);
      
    if( $encode )
      $json = json_encode($json);
      
    return $json;
  }
  
  public function replaceTags($content, $tagList=array()){
    
    return MyTools::getDI()->get('util')->replaceTags($content, $tagList);
  }
  
  public function isNew(){
    
    return $this->_isNew;
  }
  
  public function getClone(){
    
    $class = get_class($this);
    $obj = new $class();
    
    $primaryKeyList = $this->getPrimaryKey();
    $metadata       = $this->metadata();
    $attributeList  = $metadata[Phalcon\Mvc\Model\MetaData::MODELS_ATTRIBUTES];
    
    foreach ($attributeList as $attribute) {
      
      if( !in_array($attribute, $primaryKeyList) ) {

        $get = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $attribute)));
        $set = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $attribute)));
        
        $obj->$set($this->$get());
      }
    }
    
    return $obj;
  }
  
  public static function findFirstById($primaryKey, $create=false){
    
    if( !$create && !empty($primaryKey) )
      return parent::findFirstById(nvl($primaryKey, 0));
    
    if( !$create )
      return null;
    
    $obj = parent::findFirstById(nvl($primaryKey, 0));
    
    if( !is_object($obj) ){
      
      $class = get_called_class();
      $obj   = new $class();
    }
    
    return $obj;
  }

  public static function getList($criteria=null){
    
    $class = get_called_class();
    
    if( is_null($criteria) )
      $criteria = $class::query();
    
    if( property_exists($class, 'deleted') )
      $criteria->andWhere("$class.deleted = false");
      
    return $criteria->execute();
  }
  
  public static function arrayToDataList($array){
    
    $data = array();
    
    foreach( $array as $field=>$value){
      
      if( preg_match('/\_/', $field) ){
        
        $partList = explode('_', $field);
        $field    = '';
        
        foreach ($partList as $key=>$part)
          $field .= $key==0 ? $part : ucfirst($part);
      }
      
      $data[$field] = $value;
    }
    
    return $data;
  }
  
  public function toDataList(){
    
    return self::arrayToDataList($this->toArray());
  }
  
  public function skipLog(){
    
    $this->_skipLog = true;
  }
}
