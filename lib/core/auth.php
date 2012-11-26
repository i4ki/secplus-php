<?php

namespace SecPlus;

interface IAuthController {
  public function checkSession();
}

abstract class AbstractAuthController
extends AbstractController
implements IAuthController {
  protected $session_name = 'SecPlus-PHP';
  protected $token = array();

  public function __construct() {
    $this->hasSession();
  }

  protected function setupSession() {
    $this->initSession();
    $this->checkSession();
  }
 
  protected function initSession() {
    Auth::initSession($this->session_name);
  }
}

final class Auth {
  public static function initSession($name = 'SecPlus-PHP') {    
    session_name($name);
    session_start();

    if(empty($_SESSION['session_start_at'])){
      $_SESSION['session_start_at'] = time();
    }

    if(empty($_SESSION['token'])){
      $config = Config::getInstance();

      if ( ($config->getCsrfLevel() == Config::CSRF_BASIC) || 
           ($config->getCsrfLevel() == Config::CSRF_PARANOID)
         ){
        $token = md5(uniqid(rand(), TRUE));
        $_SESSION['token'] = $token;
      }
    }
  }

  public static function addSession($namevalue = array()) {
    foreach ($namevalue as $key => $value) {
      $_SESSION[$key] = $value;
    }
  }

  public static function hasSession($name) {
    return !empty($_SESSION) && session_name() == $name;
  }

  public static function destroySession() {
    session_destroy();
    unset($_SESSION);
  }
}
