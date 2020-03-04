<?php

return new \Phalcon\Config([
  'database' => [
    'adapter'  => 'Postgresql',
    'host'     => $_SERVER["MERCATOR_DB_HOSTNAME"],
    'username' => $_SERVER["MERCATOR_DB_USERNAME"],
    'password' => $_SERVER["MERCATOR_DB_PASSWORD"],
    'dbname'   => $_SERVER["MERCATOR_DB_DATABASE"],
  ],
//  'database_log' => [
//    'adapter'  => 'Postgresql',
//    'host'     => $_SERVER["MERCATOR_DB_LOG_HOSTNAME"],
//    'username' => $_SERVER["MERCATOR_DB_LOG_USERNAME"],
//    'password' => $_SERVER["MERCATOR_DB_LOG_PASSWORD"],
//    'dbname'   => $_SERVER["MERCATOR_DB_LOG_DATABASE"],
//  ],
]);
