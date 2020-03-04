<?php
use Phalcon\Di\FactoryDefault;

$app = $_SERVER['CONFIG_APP_NAME'];

$serverName = strtolower($_SERVER['SERVER_NAME']);
$httpHost   = strtolower($_SERVER['HTTP_HOST']);
$subdomain  = preg_replace('/\..*/', '', $httpHost);
$appList    = array('cron'=>'cron', 'localhost'=>'cron', 'service'=>'service');

if (array_key_exists($subdomain, $appList) && $app != 'worker') {
  $app = $appList[$subdomain];
}

/**
 * @author Luciano Stegun
 * @since 1.0 - Jul 17, 2019
 * Quando a API for chamada via ajax diretamente ela envia uma requisição do tipo OPTIONS primeiro
 * para verificar se o servidor aceita requisições cross-domain
 * Então para não ter que executar todo o código do framework, se a requisição for do tipo OPTIONS já vamos encerrar o processamento
 * e o servidor já vai retornar os headers de resposta que a requisição precisa
 */
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  exit(1);
}

define('INSTANCE', (isset($_GET['_instance']) ? $_GET['_instance'] : 'brazil'));
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/apps/'.$app);
define('ENVIRONMENT', $_SERVER['ENVIRONMENT']);
define('ENVIRONMENT_LABEL', $_SERVER['ENVIRONMENT_LABEL']);
define('SYSTEM_VERSION', $_SERVER['SYSTEM_VERSION']);
define('APP_NAME', $app);
define('REVISION', '201911200000');
define('CDN_URL', null);
define('DEBUG_ID', str_replace('.', '', microtime(true)));

if ($app == 'frontend' || $app == 'api') {
  require_once('request_log.php');
}
  
if (ENVIRONMENT == 'dev'){
  error_reporting(E_ALL);
  ini_set('display_errors', 'On');
}

try {
  
  require(BASE_PATH . '/library/Util.php');
  require(BASE_PATH . '/library/MercatorException.php');
    /**
     * The FactoryDefault Dependency Injector automatically registers
     * the services that provide a full stack framework.
     */
    $di = new FactoryDefault();

    /**
     * Read services
     */
    include APP_PATH . '/config/services.php';
    
    /**
     * Get config service for use in inline setup below
     */
    $config = $di->getConfig();

    /**
     * Include Autoloader
     */
    include APP_PATH . '/config/loader.php';
    /**
     * Handle the request
     */
    $application = new \Phalcon\Mvc\Application($di);
    
    if (ENVIRONMENT == 'dev' && $app == 'frontend' && version_compare(PHP_VERSION, '7.0.0') < 0) {
      $di['app'] = $application; //  Important
      (new Snowair\Debugbar\ServiceProvider())->start();
    }
    
    echo $application->handle()->getContent();
}catch (Exception $e) {

  if (ENVIRONMENT == 'dev') {
      echo $e->getMessage() . '<br>';
      echo '<pre>' . $e->getTraceAsString() . '</pre>';
  } else {
    
      $di->get('util')->forceError(Util::HEADER_MESSAGE_500);
  }
}
