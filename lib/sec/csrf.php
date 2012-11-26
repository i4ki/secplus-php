<?php

namespace SecPlus;

class CSRF {

  public static function verify() {    
    
    $config = Config::getInstance();
    
    if($config->getCsrfLevel() != Config::CSRF_NONE){
      $value = "";

      if(isset($_GET['token'])){
        $value = $_GET['token'];
      }else if(isset($_POST['token'])){
        $value = $_POST['token'];
      }

      return ($_SESSION['token'] == $value) ;
    }else{
      return TRUE;
    }
  }

  public static function send_token() {
    
    $config = Config::getInstance();

    if($config->getCsrfLevel() != Config::CSRF_NONE)
      return "&token=" . $_SESSION['token'];
  }

  public static function add_form_token() {
    $config = Config::getInstance();

    if($config->getCsrfLevel() != Config::CSRF_NONE)
      return "<input type=\"hidden\" name=\"token\" value=\"" . $_SESSION['token'] . "\" />";
  }        
}
