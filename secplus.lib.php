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
  protected $db_host = "127.0.0.1";

  /**
   * Database user
   * @var string
   */
  protected $db_user = "";

  /**
   * Database password
   * @var string
   */
  protected $db_pass = "";

  /**
   * Database driver
   * @var string
   */
  protected $dbms = "mysql";

  /**
   * Database name
   * @var string
   */
  protected $database = "";

  /**
   * Salt for hash algorithms
   * @var string
   */
  protected $salt = "Welcome, to the desert of the real";
  
  /**
   * Directory configuration
   */
  protected $root_project_dir;
  protected $lib_dir;
  protected $controller_dir;
  protected $model_dir;
  protected $dao_dir;
  protected $vo_dir;
  protected $view_dir;
  protected $static_dir;

  /**
   * Safe PHP files to include to prevent LFI/LFD
   * Array with every php file that is safe to include/require into project
   & @var array
   */
  protected $safe_files = array();

  /**
   * MVC Configuration
   */

  /**
   * Name of the controllers.
   * This is the name of the uri parameter that invoke the controller.
   * ex.: http://site]/?$controller=home
   * @var string
   */
  protected $controller_name = "controller";

  /**
   * Name of the action.
   * @var string
   */
  protected $action_name = "action";

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
  private function __construct() {
    $this->root_project_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $this->lib_dir = $this->root_project_dir . '/lib';
    $this->controller_dir = $this->root_project_dir . '/controller';
    $this->model_dir = $this->root_project_dir . '/model';
    $this->dao_dir = $this->model_dir . '/dao';
    $this->vo_dir = $this->model_dir . '/vo';
    $this->view_dir = $this->root_project_dir . '/view';
    $this->static_dir = Config::PROJECT_URL . '/view';
  }

  /**
   * Getter for {@link $db_user}
   * @return string
   */
  public function getDbUser() {
    return $this->db_user;
  }

  /**
   * Getter for {@link $db_pass}
   * @return string
   */
  public function getDbPass() {
    return $this->db_pass;
  }

  /**
   * Getter for {@link $database}
   * @return string
   */
  public function getDatabase() {
    return $this->database;
  }

  /**
   * Getter for {@link $dbms}
   * @return string
   */
  public function getDbms() {
    return $this->dbms;
  }

  /**
   * Getter for {@link $db_host}
   * @return string
   */
  public function getDbHost() {
         return $this->db_host;
  }

  /**
   * Getter for {@link $root_project_dir}
   * @return string
   */
  public function getRootDir() {
    return $this->root_project_dir;
  }

  /**
   * Getter for {@link $lib_dir}
   * @return string
   */
  public function getLibDir() {
    return $this->lib_dir;
  }

  /**
   * Getter for {@link $controller_dir}
   * @return string
   */
  public function getControllerDir() {
    return $this->controller_dir;
  }

  /**
   * Getter for {@link $model_dir}
   * @return string
   */
  public function getModelDir() {
    return $this->model_dir;
  }

  /**
   * Getter for {@link $dao_dir}
   * @return string
   */
  public function getDaoDir() {
    return $this->dao_dir;
  }

  /**
   * Getter for {@link $vo_dir}
   * @return string
   */
  public function getVoDir() {
    return $this->vo_dir;
  }

  /**
   * Getter for {@link $view_dir}
   * @return string
   */
  public function getViewDir() {
    return $this->view_dir;
  }

  /**
   * Getter for {@link $static_dir}
   * @return string
   */
  public function getStaticDir() {
    return $this->static_dir;
  }

  /**
   * Getter for {@link $safe_files}
   * @return array
   */
  public function getSafeFiles() {
    return $this->safe_files;
  }

  /**
   * Getter for {@link $controller_name}
   * @return string
   */
  public function getControllerName() {
      return $this->controller_name;
  }
  
  /**
   * Getter for {@link $action_name}
   * @return string
   */
  public function getActionName() {
      return $this->action_name;
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
  public static function html_redirect($url, $time) { print '<meta http-equiv="refresh" content="' . htmlentities($time) . '; url=' . $url . '">'; }
  public static function alert($msg) { print '<script type="text/javascript">alert("' . htmlentities($msg) . '");</script>'; }
}
