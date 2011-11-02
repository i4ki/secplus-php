<?php

/**
 * secplus.lib.php
 *
 * SEC+ WebFramework
 * License: GNU GPL v2.0
 * Light MVC PHP Framework designed for security.
 *
 * @author i4k - Tiago Natel de Moura <tiago4orion@gmail.com>
 * @author m0nad - Victor Ramos Mello <victornrm@gmail.com>
 *
 * @version 1.0
 * @package secplus-php
 */

namespace SecPlus;

/**
 * Configuration class
 * Extend this class to configure your project.
 *
 * *NOT* change any default configuration in this class...
 * Configure your project in the extended class.
 */
abstract class Config {

  /* Overload this constant and set the project name */
  const PROJECT_NAME = "Sec+ WebFramework";
  /* Overload this constant and set the complete url of the project */
  const PROJECT_URL = "http://www.secplus.com.br/";

  /**
   * Singleton configuration instance
   * @var Config
   */
  private static $instance;

  /**
   * Database configuration
   * *Not* change the properties here, extend this abstract class and use the getters and setters
   * to update the database configurations.
   */

  /**
   * Database host
   * @var string
   */
  protected $dbHost = "127.0.0.1";

  /**
   * Database user
   * @var string
   */
  protected $dbUser = "";

  /**
   * Database password
   * @var string
   */
  protected $dbPass = "";

  /**
   * Database driver
   * @var string
   */
  protected $dbms = "mysql";

  /**
   * Database name
   * @var string
   */
  protected $dbDatabase = "";

  /**
   * Salt for hash algorithms
   * @var string
   */
  protected $salt = "Welcome, to the desert of the real";
  
  /**
   * Directory configuration
   */
  protected $rootProjectDir;
  protected $libDir;
  protected $controllerDir;
  protected $modelDir;
  protected $daoDir;
  protected $voDir;
  protected $viewDir;
  protected $staticDir;

  /**
   * Safe PHP files to include to prevent LFI/LFD
   * Array with every php file that is safe to include/require into project
   & @var array
  */
  protected $safeFiles = array();

  protected $safeProperties = array(
                                    'rootProjectDir','libDir','controllerDir',
                                    'modelDir','daoDir','voDir','viewDir','staticDir',
                                    'dbHost','dbUser','dbPass','dbDatabase','dbms',
                                    'salt','controllerName','actionName','safeFiles'
                                    );

  /**
   * MVC Configuration
   */

  /**
   * Name of the controllers.
   * This is the name of the uri parameter that invoke the controller.
   * ex.: http://site]/?$controllerName=home
   * @var string
   */
  protected $controllerName = "controller";

  /**
   * Name of the action.
   * @var string
   */
  protected $actionName = "action";

  /**
   * Get a instance of the configuration class
   * @return Config
   */
  public static function getInstance() {
    if (isset(self::$instance))
      return self::$instance;
    else {
      $c = get_called_class();
      self::$instance = new $c();
      return self::$instance;
    }
  }

  /**
   * Constructor sets up the following properties:
   * {@link $root_project_dir}
   * {@link $lib_dir}
   * {@link $controller_dir}
   * {@link $model_dir}
   * {@link $dao_dir}
   * {@link $vo_dir}
   * {@link $view_dir}
   * {@link $static_dir}
   */
  protected function __construct() {
    $this->rootProjectDir = dirname($_SERVER['SCRIPT_FILENAME']);
    $this->libDir = $this->rootProjectDir . '/lib';
    $this->controllerDir = $this->rootProjectDir . '/controller';
    $this->modelDir = $this->rootProjectDir . '/model';
    $this->daoDir = $this->modelDir . '/dao';
    $this->voDir = $this->modelDir . '/vo';
    $this->viewDir = $this->rootProjectDir . '/view';
    $this->staticDir = Config::PROJECT_URL . '/view';
  }

  /**
   * This is a PHP magic method.
   * Implementing this method we avoid have to declare boilerplate getters and setters.
   * @param string $func
   * @param array $args
   * @return mixed
   */
  public function __call($func, $args) {
    if (preg_match('/^get/', $func) && count($args) == 0) {
      $prop = lcfirst(substr($func, 3));
      if (!empty($prop) && in_array($prop, $this->safeProperties)) {
        return $this->{$prop};
      }
    } else if (preg_match('/^set/', $func) && count($args) == 1) {
      $prop = lcfirst(substr($func, 3));
      if (in_array($prop, $this->safeProperties)) {
        $this->{$prop} = $args[0];
        return;
      }
    }

    throw new \Exception("Fatal error: Method '" . htmlentities($func) . "' not found or permission denied to be called.");
  }
}

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
      print '<span style="color: white; background-color: red;">File ' . htmlentities($filename) . ' not found or permission denied to include.</span><br><br>';
      die();
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
      print "<span style=\"color: red;\">permission denied to include the file '" . htmlentities($view_file) . "'</span>";
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
      $dbname   = $config->getDbDatabase();
	
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
  public static function html_redirect($url, $time) { print '<meta http-equiv="refresh" content="' . htmlentities($time) . '; url=' . $url . '">'; }
  public static function alert($msg) { print '<script type="text/javascript">alert("' . $msg . '");</script>'; }
}
