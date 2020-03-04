<?php

class MyRouter extends Phalcon\Mvc\Router {

  public function add($pattern, $paths = NULL, $httpMethods = NULL, $position = NULL){

    return parent::add($pattern, $paths, $httpMethods);
  }
}
