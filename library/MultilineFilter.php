<?php

use Phalcon\Validation;
use Phalcon\Filter;

class MultilineFilter {
  public function filter($value){
    return preg_replace('/\r\n/', chr(10), $value);
  }
}
