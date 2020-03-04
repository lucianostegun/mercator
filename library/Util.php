<?php
/**
 * @author Luciano Stegun
 * @since 1.0 - 05/01/2017
 */
class Util {

  const HEADER_CODE_OK                     = '200';
  const HEADER_CODE_NO_CONTENT             = '204';
  const HEADER_CODE_NOT_MODIFIED           = '304';
  const HEADER_CODE_BAD_REQUEST            = '400';
  const HEADER_CODE_UNAUTHORIZED           = '401';
  const HEADER_CODE_FORBIDDEN              = '403';
  const HEADER_CODE_NOT_FOUND              = '404';
  const HEADER_CODE_METHOD_NOT_ALLOWED     = '405';
  const HEADER_CODE_UNSUPPORTED_MEDIA_TYPE = '415';
  const HEADER_CODE_PRECONDITION_REQUIRED  = '428';
  const HEADER_CODE_INVALID_TOKEN          = '498';
  const HEADER_CODE_INTERNAL_SERVER_ERROR  = '500';
  const HEADER_CODE_NOT_IMPLEMENTED        = '501';
  const HEADER_CODE_UNAVAILABLE            = '503';

  const HEADER_MESSAGE_200 = 'OK';
  const HEADER_MESSAGE_204 = 'No Content';
  const HEADER_MESSAGE_400 = 'Bad Request';
  const HEADER_MESSAGE_401 = 'Unauthorized';
  const HEADER_MESSAGE_403 = 'Forbidden';
  const HEADER_MESSAGE_404 = 'Not Found';
  const HEADER_MESSAGE_405 = 'Method Not Allowed';
  const HEADER_MESSAGE_415 = 'Unsupported Media Type';
  const HEADER_MESSAGE_428 = 'Precondition Required';
  const HEADER_MESSAGE_498 = 'Invalid Token';
  const HEADER_MESSAGE_500 = 'Internal Server Error';
  const HEADER_MESSAGE_501 = 'Not Implemented';
  const HEADER_MESSAGE_503 = 'Unavailable';
  
  /**
   * @author Luciano Stegun
   * @since 1.0 - 05/01/2017
   * Método que retorna um cabeçalho de erro forçado para qualquer código HTTP que seja necessário
   */
  public function forceError($message=null, $exit=true, $headerCode=Util::HEADER_CODE_INTERNAL_SERVER_ERROR, $throwException=false){
  
    if( !$throwException ){
      
      $headerMessage = $this->getHeaderMessage($headerCode);
      header("HTTP/1.1 $headerCode $headerMessage");
    }
  
    if( $message ){
      
      if( is_array($message) )
        $message = json_encode($message).chr(10);
      
      if( $throwException )
        throw new MercatorException($message, $headerCode);
      else
        echo $message;
    }
  
    if( $exit )
      exit;
  }
  
  public function getHeaderMessage($headerCode){
    
    if( in_array($headerCode, array(200, 204, 400, 401, 403, 404, 405, 415, 498, 500, 501)) )
      return constant("Util::HEADER_MESSAGE_$headerCode");
    else
      return Util::HEADER_MESSAGE_500;
  }
  
  /**
   * @author Luciano Stegun
   * @since 1.0 - 05/01/2017
   * Retorna o arquivo raíz do projeto que está sendo executado na requisição (index.php, host.php, etc...)
   */
  public function scriptName(){
    
    return $_SERVER['SCRIPT_NAME'];
  }
  
  public function forceDownload($filePath, $fileName=false){
    
    if( !$fileName )
      $fileName = basename($filePath);
    
    $mimeType = mime_content_type($filePath);
    
    header('Content-Type: application/force-download');
    header('Content-disposition: attachment; filename="' . $fileName . '"'); 
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
      header('Expires: 0');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Cache-Control: cache, must-revalidate');
//	    header('Pragma: no-cache');
    header('Pragma: public');
      
      readfile($filePath);
  }
  
  public function getTempFileName($extension=null){
    
    $extension = str_replace('.', '', $extension);
    $fileName  = microtime();
    return preg_replace('/[^0-9]/', '', $fileName).($extension ? '.'.$extension : null);
  }
  
  public function getFilePath($subPath, $rootDir=null, $realPath=false){
    
    if( !$rootDir )
      $rootDir = BASE_PATH . '/web';
    
    $path = $rootDir . DIRECTORY_SEPARATOR . $subPath;
    
    $path = str_replace('[fileName]', $this->getTempFileName(), $path);
    
    return $this->fixFilePath($path, $realPath);
  }
  
  public function getRelativePath($fullPath){
    
    return str_replace(BASE_PATH . '/web/', '', $fullPath);
  }
  
  public function fixFilePath($path, $realPath=false){
    
    $path = str_replace('\\\\', DIRECTORY_SEPARATOR, $path);
    $path = str_replace('//', DIRECTORY_SEPARATOR, $path);
    
    if( $realPath )
      $path = realpath($path);
    
    return $path;
  }
  
  public function getSvnRevision(){

    $path     = $this->getFilePath('/.svn', BASE_PATH);
    $filePath = $path.'/entries';
    
    if( !$path || !file_exists($filePath) )
      return '0000';
    
    $file = file($filePath);
    if( isset($file[3]) ){
      
      $revision = $file[3];
      return sprintf('%04d', $revision);
    } else {
      
      // Check the previous directories recursively looking for wc.db (Subversion 1.7)
      $searchDepth = 3; // Max search depth
      // Do we have PDO? And do we have the SQLite PDO driver?
      if( !extension_loaded('PDO') || !extension_loaded('pdo_sqlite') )
        $searchDepth = 0; // Don't even bother...
      
      for( $i = 1; $i <= $searchDepth; $i++ ){
        
        if( !file_exists("$path/wc.db") )
          continue;

        $wcdb = new PDO("sqlite:$path/wc.db");
        $result = $wcdb->query('SELECT "revision" FROM "NODES" LIMIT 1');
        return sprintf('%04d', (int)$result->fetchColumn());
      }
    }
  }
  
  public function formatFloat($number, $decimalPlaces=2){
  
    $number = preg_replace('/[^0-9\.,\-\+]/', '', $number);
        
    if( preg_match('/-?[0-9]*\.[0-9]*,[0-9]*$/', $number) ){
      
      $number = str_replace('.', '', $number);
      $number = str_replace(',', '.', $number);
    }
  
    $number  = str_replace(',', '.', $number);
    $number *= 1.0;
    
    return (float)number_format($number, $decimalPlaces, '.', '');
  }
    
  public function formatCurrency($number, $decimalPlaces=2){
      
    $number = $this->formatFloat($number, $decimalPlaces);
      
      return number_format($number, $decimalPlaces, ',', '.');
  }
  
  public function truncateText($string, $max, $trail='...'){
    
    if( isset($string[$max]) )
      return substr($string, 0, $max - strlen($trail)).$trail;
    
    return $string;
  }
  
  public function formatDate($date, $format='Y-m-d'){
    
    if( !$date )
      return null;
    
    $date = trim(preg_replace('/[^0-9 \/\-:]/', '', $date));
    
    if( preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{2,4})( [0-9]+:[0-9]+(:[0-9]+)?)?$/', $date, $matches) ){
      
      $matches[] = ' 00:00:00';
      $timestamp = sprintf('%s-%s-%s%s', $matches[3], $matches[2], $matches[1], $matches[4]);
      $timestamp = strtotime($timestamp);
    }elseif( is_numeric($date) ){
      
      $timestamp = $date;
    } else {
      
      $timestamp = strtotime($date);
    }
     
    if (!$timestamp)
      return null;
      
    return date($format, $timestamp);
  }
  
  public function formatDateTime($dateTime, $format='Y-m-d H:i:s'){
    
    return $this->formatDate($dateTime, $format);
  }
  
  public function formatDocumentNumber($documentNumber, $documentType){
    
    if( $documentType != 'cpf' )
      return $documentNumber;
      
    $documentNumber = preg_replace('/[^0-9]/', '', $documentNumber);
    
       return preg_replace('/(...)(...)(...)(..)/', '\1.\2.\3-\4', $documentNumber);
  }
  
  /**
   * Método que retorna a extensão do arquivo
   * 
   * @author		Guilherme Limpo
    * @since 1.0 - 19/01/2017
   */
  public function getFileExtension($filePath){
    
    return preg_replace('/^.*\./', '', $filePath);
  }
  
  /**
   * Método que retorna o nome do arquivo
   * 
   * @author		Guilherme Limpo
    * @since 1.0 - 19/01/2017
   */
  public function getFileName($filePath){
  
    $filePath = preg_split('/[\\\\\/]/', $filePath);
    
    return end($filePath);
  }
  
  public function generateRandomString($length=10){
    
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string     = '';
    
    for( $i=0; $i < $length; $i++ )
      $string .= $characters[rand(0, 61)];
    
    return $string;
  }
  
  public static function noAccent($string){
    
    $translateTable = array('from' => 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåªæçèéêëìíîïðñòóôõöøùúûüýÿ',
                'to'   => 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaaceeeeiiiionoooooouuuuyy' );
    
    $noAccent = strtr(iconv('UTF-8//IGNORE', 'ISO-8859-1//IGNORE', $string ), iconv('UTF-8//IGNORE', 'ISO-8859-1//IGNORE', $translateTable['from'] ), $translateTable['to']);
    
    if( !$noAccent )
      $noAccent = self::normalizeChars($string);
    
    return $noAccent;
  }
  
  public static function normalizeChars($s) {
    $replace = array(
      'ъ'=>'-', 'Ь'=>'-', 'Ъ'=>'-', 'ь'=>'-',
      'Ă'=>'A', 'Ą'=>'A', 'À'=>'A', 'Ã'=>'A', 'Á'=>'A', 'Æ'=>'A', 'Â'=>'A', 'Å'=>'A', 'Ä'=>'Ae',
      'Þ'=>'B',
      'Ć'=>'C', 'ץ'=>'C', 'Ç'=>'C',
      'È'=>'E', 'Ę'=>'E', 'É'=>'E', 'Ë'=>'E', 'Ê'=>'E',
      'Ğ'=>'G',
      'İ'=>'I', 'Ï'=>'I', 'Î'=>'I', 'Í'=>'I', 'Ì'=>'I',
      'Ł'=>'L',
      'Ñ'=>'N', 'Ń'=>'N',
      'Ø'=>'O', 'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'Oe',
      'Ş'=>'S', 'Ś'=>'S', 'Ș'=>'S', 'Š'=>'S',
      'Ț'=>'T',
      'Ù'=>'U', 'Û'=>'U', 'Ú'=>'U', 'Ü'=>'Ue',
      'Ý'=>'Y',
      'Ź'=>'Z', 'Ž'=>'Z', 'Ż'=>'Z',
      'â'=>'a', 'ǎ'=>'a', 'ą'=>'a', 'á'=>'a', 'ă'=>'a', 'ã'=>'a', 'Ǎ'=>'a', 'а'=>'a', 'А'=>'a', 'å'=>'a', 'à'=>'a', 'א'=>'a', 'Ǻ'=>'a', 'Ā'=>'a', 'ǻ'=>'a', 'ā'=>'a', 'ä'=>'ae', 'æ'=>'ae', 'Ǽ'=>'ae', 'ǽ'=>'ae',
      'б'=>'b', 'ב'=>'b', 'Б'=>'b', 'þ'=>'b',
      'ĉ'=>'c', 'Ĉ'=>'c', 'Ċ'=>'c', 'ć'=>'c', 'ç'=>'c', 'ц'=>'c', 'צ'=>'c', 'ċ'=>'c', 'Ц'=>'c', 'Č'=>'c', 'č'=>'c', 'Ч'=>'ch', 'ч'=>'ch',
      'ד'=>'d', 'ď'=>'d', 'Đ'=>'d', 'Ď'=>'d', 'đ'=>'d', 'д'=>'d', 'Д'=>'D', 'ð'=>'d',
      'є'=>'e', 'ע'=>'e', 'е'=>'e', 'Е'=>'e', 'Ə'=>'e', 'ę'=>'e', 'ĕ'=>'e', 'ē'=>'e', 'Ē'=>'e', 'Ė'=>'e', 'ė'=>'e', 'ě'=>'e', 'Ě'=>'e', 'Є'=>'e', 'Ĕ'=>'e', 'ê'=>'e', 'ə'=>'e', 'è'=>'e', 'ë'=>'e', 'é'=>'e',
      'ф'=>'f', 'ƒ'=>'f', 'Ф'=>'f',
      'ġ'=>'g', 'Ģ'=>'g', 'Ġ'=>'g', 'Ĝ'=>'g', 'Г'=>'g', 'г'=>'g', 'ĝ'=>'g', 'ğ'=>'g', 'ג'=>'g', 'Ґ'=>'g', 'ґ'=>'g', 'ģ'=>'g',
      'ח'=>'h', 'ħ'=>'h', 'Х'=>'h', 'Ħ'=>'h', 'Ĥ'=>'h', 'ĥ'=>'h', 'х'=>'h', 'ה'=>'h',
      'î'=>'i', 'ï'=>'i', 'í'=>'i', 'ì'=>'i', 'į'=>'i', 'ĭ'=>'i', 'ı'=>'i', 'Ĭ'=>'i', 'И'=>'i', 'ĩ'=>'i', 'ǐ'=>'i', 'Ĩ'=>'i', 'Ǐ'=>'i', 'и'=>'i', 'Į'=>'i', 'י'=>'i', 'Ї'=>'i', 'Ī'=>'i', 'І'=>'i', 'ї'=>'i', 'і'=>'i', 'ī'=>'i', 'ĳ'=>'ij', 'Ĳ'=>'ij',
      'й'=>'j', 'Й'=>'j', 'Ĵ'=>'j', 'ĵ'=>'j', 'я'=>'ja', 'Я'=>'ja', 'Э'=>'je', 'э'=>'je', 'ё'=>'jo', 'Ё'=>'jo', 'ю'=>'ju', 'Ю'=>'ju',
      'ĸ'=>'k', 'כ'=>'k', 'Ķ'=>'k', 'К'=>'k', 'к'=>'k', 'ķ'=>'k', 'ך'=>'k',
      'Ŀ'=>'l', 'ŀ'=>'l', 'Л'=>'l', 'ł'=>'l', 'ļ'=>'l', 'ĺ'=>'l', 'Ĺ'=>'l', 'Ļ'=>'l', 'л'=>'l', 'Ľ'=>'l', 'ľ'=>'l', 'ל'=>'l',
      'מ'=>'m', 'М'=>'m', 'ם'=>'m', 'м'=>'m',
      'ñ'=>'n', 'н'=>'n', 'Ņ'=>'n', 'ן'=>'n', 'ŋ'=>'n', 'נ'=>'n', 'Н'=>'n', 'ń'=>'n', 'Ŋ'=>'n', 'ņ'=>'n', 'ŉ'=>'n', 'Ň'=>'n', 'ň'=>'n',
      'о'=>'o', 'О'=>'o', 'ő'=>'o', 'õ'=>'o', 'ô'=>'o', 'Ő'=>'o', 'ŏ'=>'o', 'Ŏ'=>'o', 'Ō'=>'o', 'ō'=>'o', 'ø'=>'o', 'ǿ'=>'o', 'ǒ'=>'o', 'ò'=>'o', 'Ǿ'=>'o', 'Ǒ'=>'o', 'ơ'=>'o', 'ó'=>'o', 'Ơ'=>'o', 'œ'=>'oe', 'Œ'=>'oe', 'ö'=>'oe',
      'פ'=>'p', 'ף'=>'p', 'п'=>'p', 'П'=>'p',
      'ק'=>'q',
      'ŕ'=>'r', 'ř'=>'r', 'Ř'=>'r', 'ŗ'=>'r', 'Ŗ'=>'r', 'ר'=>'r', 'Ŕ'=>'r', 'Р'=>'r', 'р'=>'r',
      'ș'=>'s', 'с'=>'s', 'Ŝ'=>'s', 'š'=>'s', 'ś'=>'s', 'ס'=>'s', 'ş'=>'s', 'С'=>'s', 'ŝ'=>'s', 'Щ'=>'sch', 'щ'=>'sch', 'ш'=>'sh', 'Ш'=>'sh', 'ß'=>'ss',
      'т'=>'t', 'ט'=>'t', 'ŧ'=>'t', 'ת'=>'t', 'ť'=>'t', 'ţ'=>'t', 'Ţ'=>'t', 'Т'=>'t', 'ț'=>'t', 'Ŧ'=>'t', 'Ť'=>'t', '™'=>'tm',
      'ū'=>'u', 'у'=>'u', 'Ũ'=>'u', 'ũ'=>'u', 'Ư'=>'u', 'ư'=>'u', 'Ū'=>'u', 'Ǔ'=>'u', 'ų'=>'u', 'Ų'=>'u', 'ŭ'=>'u', 'Ŭ'=>'u', 'Ů'=>'u', 'ů'=>'u', 'ű'=>'u', 'Ű'=>'u', 'Ǖ'=>'u', 'ǔ'=>'u', 'Ǜ'=>'u', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'У'=>'u', 'ǚ'=>'u', 'ǜ'=>'u', 'Ǚ'=>'u', 'Ǘ'=>'u', 'ǖ'=>'u', 'ǘ'=>'u', 'ü'=>'ue',
      'в'=>'v', 'ו'=>'v', 'В'=>'v',
      'ש'=>'w', 'ŵ'=>'w', 'Ŵ'=>'w',
      'ы'=>'y', 'ŷ'=>'y', 'ý'=>'y', 'ÿ'=>'y', 'Ÿ'=>'y', 'Ŷ'=>'y',
      'Ы'=>'y', 'ž'=>'z', 'З'=>'z', 'з'=>'z', 'ź'=>'z', 'ז'=>'z', 'ż'=>'z', 'ſ'=>'z', 'Ж'=>'zh', 'ж'=>'zh'
      );
      
      return strtr($s, $replace);
  }
  
  public function encrypt($data, $force=false){
    
    if( ENVIRONMENT == 'dev' && !$force )
      return $data;
//		
//		if( !$force ){
//			
//			return $data;
//			return base64_encode($data);
//		}
    
    $privateKeyPath = $this->getFilePath('library/private.pem', APP_PATH);
    $privateKey     = file_get_contents($privateKeyPath);
    
    openssl_private_encrypt($data, $response, $privateKey, OPENSSL_PKCS1_PADDING);
    return base64_encode($response);
  }
  
  public function decrypt($data, $keyType='public', $force=false){
    
    if( ENVIRONMENT == 'dev' && !$force )
      return $data;
      
    $keyPath = $this->getFilePath('library/'.$keyType.'.pem', APP_PATH);
    $key     = file_get_contents($keyPath);
    $data    = str_replace(' ', '+', $data);
    $data    = base64_decode($data);

    if( $keyType == 'public' )
      openssl_public_decrypt($data, $response, $key, OPENSSL_PKCS1_PADDING);
    else
      openssl_private_decrypt($data, $response, $key, OPENSSL_PKCS1_PADDING);

    return $response;
  }
  
  public function checkFolder($folderPath){
    
    if( !is_dir($folderPath) ){
    	
      error_log('Creating path "'.$folderPath.'"');
      $result = mkdir($folderPath, 0777, true);
    }
      
    return is_dir($folderPath);
  }
  
  public function getFullUrl($url){
    
    $schema = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
    return sprintf('%s://%s%s', $schema, $_SERVER['HTTP_HOST'], $url);
  }
  
  public function replaceTags($content, $tagList=array()){
    
    $keyList   = array_keys($tagList);
    $keyList   = explode(',', '['.implode('],[', $keyList).']');
    $valueList = array_values($tagList);
    
    return str_replace($keyList, $valueList, $content);
  }
  
  public static function executeQuery($query, $con=null){
    
    if( is_string($con) )
      $con = MyTools::getDi()->get($con);
      
    if( is_null($con) )
      $con = MyTools::getDi()->get('db');
    
      return $con->query($query);
  }
  
  public static function executeOne($query, $returnType='int', $con=null){

    $resultSet = self::executeQuery($query, $con)->fetchAll(PDO::FETCH_NUM);
      
      foreach ($resultSet as $result)
        return $result[0];
  }
  
  public static function executeList($query, $returnType='int', $con=null){

    $resultSet = self::executeQuery($query, $con)->fetchAll(PDO::FETCH_NUM);
      
      $resultList = array();
      
      foreach ($resultSet as $result)
        $resultList[] = $result[0];
      
      return $resultList;
  }
  
  public function parseSize($size){
    
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
    $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
    
    if( $unit )
      return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    else
      return round($size);
  }
  
  public function formatSize($bytes, $precision = 2) { 
    
    if( $bytes >= 1024 * 1024 * 1024 )
      $bytes /= 1024 * 1024 * 1024;
    elseif( $bytes >= 1024 * 1024 )
      $bytes /= 1024 * 1024;
    elseif( $bytes >= 1024 )
      $bytes /= 1024;
    
    return $this->formatFloat($bytes, 2).' GB';
  }
  
  public function formatTimeString($seconds, $format=null, $convertDays=true){
    
    $seconds = ($seconds?$seconds:0);
    
    $days = ceil($seconds/86400);
    $hours = ceil($seconds/86400);
    
    $days = floor($seconds/86400);
    $seconds = $seconds - ($days*86400);
  
    $hours = floor($seconds/3600);
    $seconds = $seconds - ($hours*3600);
  
    $minutes = floor($seconds/60);
    $seconds = floor($seconds - ($minutes*60));
    
    $days    = sprintf('%02d', $days);
    $hours   = sprintf('%02d', $hours);
    $minutes = sprintf('%02d', $minutes);
    $seconds = sprintf('%02d', $seconds);
    
    $timeString = array();
    $timeString['days']    = $days;
    $timeString['d']       = $days;
    $timeString['hours']   = $hours;
    $timeString['h']       = $hours;
    $timeString['minutes'] = $minutes;
    $timeString['m']       = $minutes;
    $timeString['seconds'] = $seconds;
    $timeString['s']       = $seconds;
    
    if( $format ){
      switch ($format) {
        case '%h:%m:%s':
        case 'h:i:s':
          return sprintf('%02d', ($hours+($days*24))).':'.$minutes.':'.$seconds;
        case '%h:%m':
        case 'h:i':
          return sprintf('%02d', ($hours+($days*24))).':'.$minutes;
        case '%hh%mm':
          return sprintf('%02d', ($hours+($days*24))).'h'.$minutes.'m';
        default:
          if( $convertDays )
            $hours = sprintf('%02d', $hours + $days * 24);
            
          $format = preg_replace('/%d/i', $days, $format);
          $format = preg_replace('/%h/i', $hours, $format);
          $format = preg_replace('/%m/i', $minutes, $format);
          $format = preg_replace('/%s/i', $seconds, $format);
          return $format;
      }
        
      return $timeString[$format];
    }

    return sprintf('%02d', ($hours+($days*24))).'h '.$minutes.'m '.$seconds.'s';
  }

  public function loadQuery($path, $parameters=array()) {

    $path     = preg_replace('/\.sql$/i', '', $path);
    $filePath = $this->getFilePath('/../sql/' . $path . '.sql', null, true);
    $sql      = file_get_contents($filePath);

    return $sql;
  }
}

/**
 * @author Guilherme Limpo
 * @since 1.0 - 18/01/2017
 * Método que gera uma saída de options para campos do tipo select
 */
function optionsForSelect($options, $default=''){

  $html = '<options>';

  foreach ($options as $value=>$description)
    $html .= '<option value="'.$value.'"'.($value==$default?' selected="1"':'').'>'.$description.'</option>';

  $html .= '</options>';

  return $html;
}

function optionsForSelectAjax($options, $default=''){

  $optionList = array();

  foreach ($options as $value=>$description)
    $optionList[] = array('id' => $value, 'text' => $description);

  return json_encode($optionList);
}

/**
 * @author Luciano Stegun
 * @since 1.0 - 05/01/2017
 * Método que gera uma saída para debugs no browser
 */
function prexit($value, $noExit=false, $force=false){

  if( !$force && ENVIRONMENT == 'prod' && !isset($_GET['debug']) )
    return;

  $isCli = (php_sapi_name() == 'cli');
  
  if( $isCli ){
    
    print_r($value);
    echo PHP_EOL;
  } else {
    
    echo sprintf('<pre>%s</pre>', print_r($value, true));
    echo PHP_EOL;
  }
  
  if( !$noExit )
    exit;
}

/**
 * @param  $value - Valor da variável
 * @param  $nullValue - Valor para substituir, caso $value esteja vazio ou nulo (opcional)
 * @author Luciano Stegun
 * @since 1.0 - 11/01/2017
 * Método que para substituir valores nulos e vazios
 */
function nvl($value, $nullValue=null){

  return empty($value) && (string)$value !== '0' ? $nullValue : $value;
}

/**
 * @author Luciano Stegun
 * @since 1.0 - 27/01/2017
 * @param  $value
 * @param  $plural
 * @param  $singular
 * Método que retorna um texto para quando o valor de referência for plural ou outro valor para quando for singular
 */
function getPlural($value, $plural='s', $singular='', $zero=null){

  if( is_null($zero) )
    $zero = $plural;
    
  if( $value == 0 )
    return $zero;
  elseif( $value == 1 )
    return $singular;
  else
    return $plural;
}

function truncateText($text, $max, $tail='...'){
  
  if( strlen($text) > $max )
    return substr($text, 0, $max - strlen($tail)).$tail;
  
  return $text;
}

function numberToRoman($num){

  // Make sure that we only use the integer portion of the value
  $n = intval($num);
  
  $result = '';
  
  // Declare a lookup array that we will use to traverse the number:
  $lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
  foreach ($lookup as $roman => $value) {

    // Determine the number of matches
    $matches = intval($n / $value);

    // Store that many characters
    $result .= str_repeat($roman, $matches);

    // Substract that from the number
    $n = $n % $value;
  }
  
  // The Roman numeral should be built, return it
  return $result;
}

function debug($message){
  
  $fp = fopen(MyTools::getDi()->get('util')->getFilePath('../log/debug.log'), 'a');
  fwrite($fp, date('Y-m-d H:i:s') . " - $message\n");
  fclose($fp);
}