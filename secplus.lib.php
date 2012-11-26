<?php

/**
 * secplus.lib.php
 *
 * SEC+ WebFramework
 * License: GNU GPL v2.0
 * Light MVC PHP Framework designed for security.
 *
 * @author i4k - Tiago Natel de Moura <tiago4orion@gmail.com>
 *
 * @version 1.0
 * @package secplus-php
 */
namespace SecPlus;

require_once "lib/cfg/config.php";
require_once "lib/core/controller.php";
require_once "lib/core/model.php";
require_once "lib/core/valueobject.php";

/**
 * Main class
 */
class WebFramework {

  protected $config;
  protected $controller;

  public function __construct($conf) {
    $this->config = $conf;

    spl_autoload_register(array($this, 'autoload'));
    $this->handleController();
  }

  /**
   * Loader for the SecPlus classes.
   */
  private function autoload($classname) {
    $filename = "";

    /**
     * First, we attempt to discover if the class is part of SecPlus-PHP
     * framework.
     */
    $cfg = Config::getInstance();
    $class_files = $cfg->getLibrary();
    $classname = str_replace(__NAMESPACE__ . '\\', '', $classname);
      
    if (!empty($class_files[$classname])) {
      $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . strtolower($class_files[$classname]);
      if (file_exists($filename)) {
        require_once $filename;
      } else {
        print "Something went wrong here! <br>The file $classname of SecPlus-PHP library was not found ... check your library package.<br>";
        die();
      }
    } else {
      $filename = "";

      if (preg_match('/Controller$/', $classname)) {
        $filename = $this->config->getControllerDir();
      } else if (preg_match('/DAO$/', $classname)) {
        $filename = $this->config->getDaoDir();
      } else if (preg_match('/View$/', $classname)) {
        $filename = $this->config->getViewDir();
      } else if (file_exists($this->config->getVoDir() . DIRECTORY_SEPARATOR .  $classname . '.php')) {
        $filename = $this->config->getVoDir();
      } else {
        $filename = DIRECTORY_SEPARATOR . $classname . '.php';
      }

      if (!empty($filename)) {
        $filename .=  DIRECTORY_SEPARATOR .  $classname . '.php';
        /**
         * Security against LFI/LFD
         * Each file that needs to be dynamically included, *MUST* be defined in the configuration class.
         */
        if (file_exists($filename) && in_array($filename, $this->config->getSafeFiles()))
          require $filename;
        else {
          Helper::throwPermissionDeniedInclude($filename);
        }
      } else {
        Helper::throwPermissionDeniedInclude($filename);
      }
    }
  }

  /**
   * Controller manager.
   * Identify the controller and execute.
   */
  protected function handleController() {
    $c = Config::getInstance();
      
    $controllerName = $c->getControllerName();
    $action_name = $c->getActionName();
    if (!empty($_GET[$controllerName])) {
      $controller = $_GET[$controllerName];
    } else {
      $controller = $c->getDefaultController();  // If any controller, this is the default.
    }

    $class = ucfirst($controller) . 'Controller';
    $c = new $class();
    $c->_setupController();
    $c->setup();
  }

  public static function getRootDir() {
    return dirname(__FILE__);
  }
}

if (php_sapi_name() == "cli") {
  require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/cmd/shell.php';
  secplus_cmd();
 }

function secplus_cmd() {
  global $argc;
  global $argv;
  $config_file = 'config.php';

  for ($i = 0; $i < $argc; $i++) {
    if ($argv[$i] == "-c") {
      if ($i < ($argc - 1)) {
        $config_file = $argv[$i+1];
      }
    }
  }
  
  $secshell = new Shell($config_file);
  $secshell->loopExecute();
}
