<?php

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Date as DateValidator;
use Phalcon\Filter;
use Phalcon\Validation\Validator\Confirmation;
use Phalcon\Validation\Validator\Regex as RegexValidator;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Callback;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Numericality;

class MyValidation extends Phalcon\Validation {

  private $errorList            = array();
  private $customValidationList = array();
  private $exclusiveMethod      = null;
  private $displayErrors        = true;
  
  public function __construct(){
    
    $filter = new Filter();
    $filter->add('multiline', new MultilineFilter());
    $this->di->get('filter')->add('multiline', new MultilineFilter());
  }
  
  public function validateErrors(){
    
       foreach ($this->validate($this->request->get()) as $key=>$message)
         if( !$this->hasMessagesFor($message->getField()) )
           $this->errorList[$message->getField()] = $message->getMessage();
      
       $this->validateCustom();
           
      if( !empty($this->errorList) ){
        
        if( $this->displayErrors ){
          
          $this->errorList = json_encode(array('status' => 'form-error', 'fields' => $this->errorList));
          $this->util->forceError($this->errorList, true, Util::HEADER_CODE_BAD_REQUEST);
        } else {
          
          throw new MercatorException('error');
        }
      }
  }
  
  public function setMethod($method){
    
    $this->exclusiveMethod = $method;
  }
  
  public function addRequired($fieldName, $message='Required field', $fieldNameError=null){

    $fieldNameError = nvl($fieldNameError, $fieldName);
    
    if( !$this->request->get($fieldName) )
      $this->addError($fieldNameError, $message);
  }
  
  public function addEmail($fieldName){

    if( $this->request->get($fieldName) )
      $this->add($fieldName, new Email(array('message' => 'E-mail inválido')));
  }
  
  public function addDate($fieldName, $format='d/m/Y', $fieldNameError=null){
    
    $fieldValue     = $this->request->get($fieldName);
    $fieldNameError = nvl($fieldNameError, $fieldName);
    
    if (!$fieldValue)
      return;
    
    $dateValue = MyTools::getDi()->get('util')->formatDate($fieldValue, $format);
    
    if (!$dateValue)
      $this->addError($fieldNameError, 'Invalid date');
  }
  
  public function addTime($fieldName, $fieldNameError=null){
    
    $fieldValue     = $this->request->get($fieldName);
    $fieldNameError = nvl($fieldNameError, $fieldName);
    
    if ($fieldValue && !preg_match('/(([0-1][0-9])|([2][0-3])):([0-5][0-9])(:([0-5][0-9]))?/', $fieldValue))
      $this->addError($fieldNameError, 'Invaild time');
  }

  public function addDateTime($fieldName, $format='d/m/Y H:i'){
    
    $fieldValue = $this->request->get($fieldName);
    
    if ($fieldValue )
      $this->add($fieldName, new DateValidator(array('format' => $format, 'message' => 'Invalid date/time')));
  }
  
  public function addError($fieldName, $message){
    
    if( !$this->hasMessagesFor($fieldName) )
      $this->errorList[$fieldName] = $message;
  }
  
  public function hasMessagesFor($fieldName){
    
    return isset($this->errorList[$fieldName]);
  }
  
  public function getErrorList(){
    
    return $this->errorList;
  }
  
  protected function validateCustom(){
  
    foreach ($this->customValidationList as $customValidation) {
      
      $fieldName = $customValidation['fieldName'];
      
      if( $this->hasMessagesFor($fieldName) )
         continue;
         
      $className = $customValidation['className'];
      $method    = $customValidation['method'];
      $message   = $customValidation['message'];
       
       if( !$className::$method($this->request->get($fieldName), $this) )
         $this->addError($fieldName, $message);
    }
  }
  
  public function addRegExp($field, $pattern, $message){
    
    $pattern = preg_replace('/^\/(.*)\/$/', '\1', $pattern);
    
    $this->add($field, new RegexValidator(['pattern' => '/'.$pattern.'/', 'message' => $message]));
  }
  
  public function addString($fieldName, $minLength=null, $maxLength=null, $fieldLabel=null, $messageMinimum=null, $messageMaximum=null){
    
    $messageMinimum = $messageMinimum ? $messageMinimum : $this->getMinLengthMessage($fieldLabel, $minLength);
    $messageMaximum = $messageMaximum ? $messageMaximum : $this->getMaxLengthMessage($fieldLabel, $maxLength);
    
    $this->add($fieldName, new StringLength(array('min' => $minLength, 'max' => $maxLength, 'messageMinimum' => $messageMinimum, 'messageMaximum' => $messageMaximum)));
  }
  
  public function addNumber($fieldName, $minValue=null, $maxValue=null, $minMessage=null, $maxMessage=null){
    
    $fieldValue = $this->request->get($fieldName);
    
    $minMessage = nvl($minMessage, 'Valor mínimo: ' . $minValue);
    $maxMessage = nvl($maxMessage, 'Valor máximo: ' . $maxValue);
    
    if( $fieldValue )
      $this->add($fieldName, new Numericality(array('message' => 'Número inválido')));
    
    if( $minValue !== null && $fieldValue !== null && $fieldValue !== ''  )
      if( is_numeric($fieldValue) && $fieldValue * 1.0 < $minValue )
        $this->addError($fieldName, $minMessage);
    
    if( $maxValue !== null && $fieldValue !== null && $fieldValue !== '' )
      if( is_numeric($fieldValue) && $fieldValue * 1.0 > $maxValue )
        $this->addError($fieldName, $maxMessage);
  }

  public function addConfirmation($field, $fieldCompare, $message){
    
    $this->add($field, new Confirmation(['message' => $message, 'with' => $fieldCompare]));
  }
  
  public function addCustom($fieldName, $className, $method, $message, $fieldNameMessage=null){
    
    $fieldNameMessage = $fieldNameMessage ? $fieldNameMessage : $fieldName;
    
    $this->customValidationList[] = array(
      'fieldName' => $fieldNameMessage,
      'className' => $className,
      'method'    => $method,
      'message'   => $message,
    );
  }
  
  public function getMinLengthMessage($label, $value){
    return sprintf('The %s must be at least %s characters', $label, $value);
  }
  
  public function getMaxLengthMessage($label, $value){
    return sprintf('You have exceeded the maximum number of %s characters in the field %s', $value, $label);
  }
  
  /**
   * string		Strip tags and escapes HTML entities, including single and double quotes.
   * email		Remove all characters except letters, digits and !#$%&*+-/=?^_`{|}~@.[].
   * int			Remove all characters except digits, plus and minus sign.
   * float		Remove all characters except digits, dot, plus and minus sign.
   * alphanum		Remove all characters except [a-zA-Z0-9]
   * striptags	Applies the strip_tags function
   * trim			Applies the trim function
   * lower		Applies the strtolower function
   * upper		Applies the strtoupper function
   * */
  public function filter($fieldName, $filter, $method=null){
    
    if( $this->exclusiveMethod != null )
      $method = $this->exclusiveMethod;
    
    if( isset($_POST[$fieldName]) ){
      
      $value = $this->request->getPost($fieldName, $filter);
      $_POST[$fieldName] = $value;
    }
    
    if( isset($_REQUEST[$fieldName]) ){
      
      $value = $this->request->get($fieldName, $filter);
      $_REQUEST[$fieldName] = $value;
    }
    
    if( isset($_GET[$fieldName]) ){
      
      $value = $this->request->getQuery($fieldName, $filter);
      $_GET[$fieldName] = $value;
    }
    
    if( strtolower($method) == 'get' && isset($_POST[$fieldName]) ){
      
      unset($_POST[$fieldName]);
      
      if( isset($_GET[$fieldName]) )
        $_REQUEST[$fieldName] = $_GET[$fieldName];
      elseif( isset($_REQUEST[$fieldName]) )
        unset($_REQUEST[$fieldName]);
    }
    
    if( strtolower($method) == 'post' && isset($_GET[$fieldName]) ){
      
      unset($_GET[$fieldName]);
      
      if( isset($_POST[$fieldName]) )
        $_REQUEST[$fieldName] = $_POST[$fieldName];
      elseif( isset($_REQUEST[$fieldName]) )
        unset($_REQUEST[$fieldName]);
    }
  }
  
  
  
  
  
  public static function validateCpf($cpf, MyValidation &$validator){
    
    /* Retira todos os caracteres que nao sejam 0-9 */
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    $sum = 0;

    if( strlen($cpf) <> 11 )
      return false;

    if( $cpf == '00000000000' )
      return false;

    $nullList = array('12345678909', '11111111111', '22222222222', '33333333333', '44444444444', '55555555555', '66666666666', '77777777777', '88888888888', '99999999999', '00000000000');

    /* Retorna falso se o cpf for nulo */
    if( in_array($cpf, $nullList) )
      return false;
    
    /*Calcula o penúltimo dígito verificador*/
    $acum=0;
    for( $i=0; $i<9; $i++ )
      $acum+= $cpf[$i]*(10-$i);
    
    $x=$acum % 11;
    $acum = ($x>1) ? (11 - $x) : 0;
    
    /* Retorna falso se o digito calculado eh diferente do passado na string */
    if( $acum*1 != $cpf[9]*1 )
      return false;

    /*Calcula o último dígito verificador*/
    $acum=0;
    
    for( $i=0; $i<10; $i++ )
      $acum+= $cpf[$i]*(11-$i);
    
    $x=$acum % 11;
    $acum = ($x > 1) ? (11-$x) : 0;
    /* Retorna falso se o digito calculado eh diferente do passado na string */
    if( $acum*1 != $cpf[10]*1 )
      return false;

    /* Retorna verdadeiro se o cpf eh valido */
    return true;
  }
  
  public function setDisplayErrors($displayErrors){
    
    $this->displayErrors = $displayErrors;
  }
}