<?php

namespace SecPlus;

interface IValueObject {

}

abstract class AbstractValueObject implements IValueObject {
  protected $_data = array();

  public function __set($name, $val) {
    $this->_data[$name] = array($val, \PDO::PARAM_STR);
  }

  public function __get($name) {
    if (array_key_exists($name, $this->_data)) {
      return $this->_data[$name][0];
    } else {
      return NULL;
    }
  }

  public function __isset($name) {
    return isset($this->_data[$name]);
  }

  public function __unset($name) {
    unset($this->_data[$name]);
  }
  
  public function __call($func, $args) {
    if (preg_match('/^get/', $func) && count($args) == 0) {
      $prop = lcfirst(substr($func, 3));
      if (!empty($prop)) {
        return $this->_data[$prop][0];
      }
    } else if (preg_match('/^set/', $func)) {
      $prop = lcfirst(substr($func, 3));
      if (!empty($prop) && count($args) > 0) {
        $val_type = \PDO::PARAM_STR;
        if (count($args) > 1) {
          $val_type = $args[1];
        }
        $this->_data[$prop] = array($args[0], $val_type);
        return;
      }
    }
  }

  public function getData() {
    return $this->_data;
  }
}
