<?php

use Phalcon\Events\Event;
use Phalcon\Mvc\User\Plugin;
use Phalcon\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatcherException;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;

/**
 * NotFoundPlugin
 *
 * Handles not-found controller/actions
 */
class NotFoundPlugin extends Plugin {

  /**
   * This action is executed before execute any action in the application
   *
   * @param Event $event
   * @param MvcDispatcher $dispatcher
   * @param Exception $exception
   * @return boolean
   */
  public function beforeException(Event $event, MvcDispatcher $dispatcher, Exception $exception){

    if( $exception instanceof DispatcherException ){
      
      switch ($exception->getCode()) {
        case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
        case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
        
          /**
           * @author Luciano Stegun
           * Antes de dispararmos uma exception de NOT FOUND vamos ver se a aplicação está configurada para ser usada como proxy
           *  
           * @author Luciano Stegun
           * @since 1.0 - Jul 15, 2019
           * Se for para usar a API abertamente, usar API_MODE = direct
           * Se for para usar a API sendo chamada pelo frontend, usar API_MODE = proxy
           */
          if (APP_NAME == 'frontend' && API_MODE == 'proxy' && ($this->request->isAjax())) {
            MercatorApi::forward();
          }
        
          $dispatcher->forward(array(
            'controller' => 'errors',
            'action' => 'show404'
          ));
          return false;
      }
    }
    
    error_log($exception->getMessage() . PHP_EOL . $exception->getTraceAsString());

    $dispatcher->forward(array(
      'controller' => 'errors',
      'action'     => 'show500',
      'params'     => array($exception)
    ));
    
    return false;
  }
}
