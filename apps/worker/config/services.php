<?php

use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;
use Phalcon\Queue\Beanstalk;

/**
 * Shared configuration service
 */
$di->setShared('config', function(){
  return include APP_PATH . '/config/config.php';
});

if( php_sapi_name() != 'cli' ){
  
  $di->setShared('view', function(){
    $config = $this->getConfig();
  
    $view = new View();
    $view->setDI($this);
    $view->setViewsDir($config->application->viewsDir);
    
    $view->registerEngines(['.phtml' => PhpEngine::class]);
  
    return $view;
  });
}

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function(){
    $config = $this->getConfig();

  $class = $config->database->adapter;
    $params = [
      'host'     => $config->database->host,
      'username' => $config->database->username,
      'password' => $config->database->password,
      'dbname'   => $config->database->dbname,
    ];

  $connection = new $class($params);

  return $connection;
});

$di->setShared('db_log', function(){
    $config = $this->getConfig();

  $class = $config->database->adapter;
    $params = [
      'host'     => $config->database_log->host,
      'username' => $config->database_log->username,
      'password' => $config->database_log->password,
      'dbname'   => $config->database_log->dbname,
    ];

  $connection = new $class($params);

  return $connection;
});

$di->set('util', function(){
  $util = new Util();
  return $util;
});

$di->set('queue', function(){
  
  $config = $this->getConfig();
  
  $queue = new Beanstalk([
    'host'       => $config->queue->host, 
    'port'       => $config->queue->port, 
    'persistent' => true
  ]);
  
  return $queue;
});