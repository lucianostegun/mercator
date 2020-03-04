<?php
use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;

define('BASE_PATH', realpath(__DIR__ . '/../..'));
define('APP_PATH', __DIR__);
define('APP_NAME', 'worker');
define('STARTED_AT', time());
define('ENVIRONMENT', $_SERVER['ENVIRONMENT']);
define('SYSTEM_VERSION', $_SERVER['MERCATOR_SYSTEM_VERSION']);
define('DEBUG', in_array('--debug', $argv));


if (DEBUG) {
  error_reporting(E_ALL);
  ini_set('display_errors', 'On');
  define('DEBUG_ID', str_replace('.', '', microtime(true)));
} else {
  error_reporting(null);
  ini_set('display_errors', 'Off');
}

require(BASE_PATH . '/library/Util.php');

// Using the CLI factory default services container
$di = new CliDI();

/**
 * Process the console arguments
 */
$arguments = [];

foreach ($argv as $k => $arg) {
  
  if( $k === 1 ){
    
    /**
     * @author Luciano Stegun
     * @since 1.0 - Jul 23, 2019
     * Correção para funcionar no php7 porque quando chamava uma task com nome camelizado não estava funcionando
     */
    $arg = strtolower(preg_replace('/([a-z])([A-Z])/', '\1-\2', $arg));
    $arguments['task'] = $arg;
  }elseif ($k === 2){
    
    $arguments['action'] = $arg;
  }elseif ($k >= 3){
    
    
    parse_str($arg, $paramList);
    foreach ($paramList as $field=>$value){
    	
      if ($value === '' || preg_match('/^--/', $field))
        $value = true;
        
      break;
    }
    
    $arguments['params'][] = $arg;
    $arguments['params'][$field] = $value;
  }
}


try {
  
  include APP_PATH . '/config/services.php';
  
  $config = $di->getConfig();
  
  include APP_PATH . '/config/loader.php';
  
  // Create a console application
  $console = new ConsoleApp();
  $console->setDI($di);

  // Handle incoming arguments
  $console->handle($arguments);
}catch (\Phalcon\Exception $e) {

  // Do Phalcon related stuff here
  // ..
  fwrite(STDERR, $e->getMessage() . PHP_EOL);
  exit(1);
}catch (\Throwable $throwable) {
  
  fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
  exit(1);
}catch (\Exception $exception) {
  
  fwrite(STDERR, $exception->getMessage() . PHP_EOL);
  exit(1);
}
