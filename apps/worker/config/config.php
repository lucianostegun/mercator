<?php

defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/apps/worker');
$config = require(BASE_PATH . '/apps/config/config.php');

return new \Phalcon\Config([
    'application' => [
        'appDir'         => APP_PATH . '/',
        'controllersDir' => APP_PATH . '/controllers/',
        'tasksDir'       => APP_PATH . '/tasks/',
        'modelsDir'      => APP_PATH . '/models/',
        'modelsBaseDir'  => APP_PATH . '/models/base/',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'viewsDir'       => APP_PATH . '/views/',
        'pluginsDir'     => APP_PATH . '/plugins/',
        'libraryDir'     => APP_PATH . '/library/',
        'cacheDir'       => BASE_PATH . '/cache/',
        'baseUri'        => '/',
    ],
    'database' => [
        'adapter'     => 'My' . $config->database->adapter,
        'host'        => $config->database->host,
        'username'    => $config->database->username,
        'password'    => $config->database->password,
        'dbname'      => $config->database->dbname,
    ],
//    'database_log' => [
//        'adapter'     => 'My' . $config->database->log->adapter,
//        'host'        => $config->database->log->host,
//        'username'    => $config->database->log->username,
//        'password'    => $config->database->log->password,
//        'dbname'      => $config->database->log->dbname,
//    ],
//    'queue' => [
//        'host'        => $_SERVER['MERCATOR_QUEUE_HOSTNAME'],
//        'port'        => $_SERVER['MERCATOR_QUEUE_PORT'],
//        'username'    => null,
//        'password'    => null,
//    ],
    'project' => [
        'libraryDir'    => BASE_PATH . '/library/',
        'vendorDir'     => BASE_PATH . '/library/vendor/',
        'modelsDir'     => BASE_PATH . '/models/',
        'modelsBaseDir' => BASE_PATH . '/models/base/',
    ],
    'debug' => true,
    'version' => SYSTEM_VERSION,
]);