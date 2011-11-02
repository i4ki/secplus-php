<?php

/**
 * SEC+ WebFramework
 * License: GNU GPL v2.0
 * Light MVC PHP Framework designed for security.
 *
 * Authors: i4k   - Tiago Natel de Moura
 *          m0nad - Victor Ramos Mello
 */

namespace SecPlus;

class WebFramework {

  protected $config;
  protected $controller;

  public function __construct() {
    $this->config = Config::getInstance();

    spl_autoload_register(array($this, 'autoload'));
    $this->handleController();
  }

  /**
   * Loader for the SecPlus classes.
   */
  private function autoload($classname) {
    $filename = "";

    if (preg_match('/Controller$/', $classname)) {
      $filename = $this->config->getControllerDir();
    } else if (preg_match('/DAO$/', $classname)) {
      $filename = $this->config->getDaoDir();
    } else if (preg_match('/View$/', $classname)) {
      $filename = $this->config->getViewDir();
    }
    
    $filename .=  DIRECTORY_SEPARATOR .  $classname . '.php';

    /**
     * Security against LFI/LFD
     * Each file that needs to be dynamically included, *MUST* be defined in the configuration class.
     */
    if (file_exists($filename) && in_array($filename, $this->config->getSafeFiles()))
      require $filename;
    else {
      print '<span style="color: white; background-color: red;">File ' . $filename . ' not found or permission denied to include.</span><br><br>';
    }
  }

  /**
   * Controller manager.
   * Identify the controller and execute.
   */
  protected function handleController() {
    $c = Config::getInstance();
      
    $controller_name = $c->getControllerName();
    $action_name = $c->getActionName();
    if (!empty($_GET[$controller_name])) {
      $controller = $_GET[$controller_name];
    } else {
      $controller = "home";  // If any controller, this is the default.
    }

    $class = ucfirst($controller) . 'Controller';
    $c = new $class();
    $c->setup();
  }
}

/**
 * Interface for controllers
 */
interface IController {
  /**
   * SecPlus\WebFramework automatically call this method for set up the controller
   */
  public function setup();
}

/**
 * Every Controller need extend this abstract class
 */
abstract class AbstractController implements IController {
  /**
   * Singleton instance of the Config class.
   */
  protected $config;

  /**
   * Renderize the view
   *
   * $view is the name of the view to render.
   * $arr_vars is an array of variables to export to be visible in the view file context.
   */
  public function render($view, $arr_vars) {
    $view_file = $this->config->getViewDir() . DIRECTORY_SEPARATOR . $view . 'View.php';
    $safe_files = $this->config->getSafeFiles();

    if (in_array($view_file, $safe_files)) {
      extract($arr_vars);
      include $view_file;
    } else {
      print "<span style=\"color: red;\">permission denied to include the file '$view_file'</span>";
      die();
    }
  }
}

/**
 * Every Model need extend this abstract class
 */
abstract class AbstractModel implements IModel {
  /**
   * Singleton instance of the database connection
   */
  private static $conn = null;
  
  public function connect() {
    self::$conn = Database::getConnection();
  }
}

/**
 * Interface to Models
 */
interface IModel {
  
}

/**
 * Manages the database
 */
class Database {
  private static $conn = null;

  private function __construct() {
    /**
     * *Never* try to instantiate this class
     */
  }

  /**
   * Connect to database
   */
  public static function getConnection() {
    try {
      $config = Config::getInstance();

      $dbms = $config->getDbms();
      $host = $config->getDbHost();
      $user = $config->getDbUser();
      $pass = $config->getDbPass();
      $dbname   = $config->getDatabase();
	
      $datasource = $dbms . ":" . "host=" . $host . ";dbname=" . $dbname;
      
      self::$conn = new \PDO($datasource, $user, $pass);
      self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); // Set Errorhandling to Exception

      return self::$conn;

    } catch (PDOException $e) {
      print "database connection error.";
      die();
    } catch (Exception $e) {
      print "error: " . $e->getMessage();
    }    
  }

  public static function closeConnection() {
    self::$conn = null;
  }
}

/**
 * Helpers to aid in develop.
 */
final class Helper {
  public static function http_redirect($url) { header("Location: $url"); }
  public static function html_redirect($url, $time) { print '<meta http-equiv="refresh" content="' . $time . '; url=' . $url . '">'; }
  public static function alert($msg) { print '<script type="text/javascript">alert("' . $msg . '");</script>'; }
}
