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
  
  const ENV_PRODUCTION = 0;
  const ENV_DEVELOPMENT = 1;

  /**
   * Singleton configuration instance
   * @var Config
   */
  private static $instance;

  private $environment = Config::ENV_DEVELOPMENT;

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

  /**
   * Safe PHP properties that can be used with __call.
   * This prevent unrestricted php code execution.
   * @var array
   */
  protected $safeProperties = array(
                                    'rootProjectDir','libDir','controllerDir',
                                    'modelDir','daoDir','voDir','viewDir','staticDir',
                                    'dbHost','dbUser','dbPass','dbDatabase','dbms',
                                    'salt','controllerName','actionName','safeFiles',
                                    'defaultController', 'defaultAction', 'defaultTitle'
                                    );

  /**
   * Default controller to be called in case of anyone controller specified
   * in the URL.
   * @var string
   */
  protected $defaultController = "home";

  /**
   * Default action to be called in case of anyone action specified in
   * the URL.
   * @var string
   */
  protected $defaultAction = "view";

  /**
   * MVC Configuration
   */

  /**
   * Name of the controllers.
   * This is the name of the uri parameter that invoke the controller.
   * ex.: http://site]/?[controllerName]=home
   * @var string
   */
  protected $controllerName = "controller";

  /**
   * Name of the action.
   * @var string
   */
  protected $actionName = "action";

  /**
   * Default title for pages
   * @var string
   */
  protected $defaultTitle = "SEC+ Security Architecture for Enterprises";

  /**
   * Get a Singleton instance of the configuration class
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
    $this->staticDir = $this->getProjectUrl() . '/view';
  }

  public function setEnvironment($env) { $this->environment = $env; }
  public function getEnvironment() { return $this->environment; }
  public function isDebug() { return $this->getEnvironment() == Config::ENV_DEVELOPMENT; }
  public static function getHost() { return php_sapi_name() == 'cli' ? 'cli' : $_SERVER['HTTP_HOST']; }
  public static function getProjectUrl() { return "http://" . self::getHost() . $_SERVER['SCRIPT_NAME']; }
  public static function getProjectBaseUrl() { return "http://" . self::getHost() . dirname($_SERVER['SCRIPT_NAME']); }
  
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

    if ($this->environment == Config::ENV_DEVELOPMENT) {
      throw new \Exception("Fatal error: Method '" . htmlentities($func) . "' not found or permission denied to be called.");
    } else {
      /* blind error */
      throw new \Exception("Fatal error!");
    }
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
      Helper::throwPermissionDeniedInclude($filename);
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

  protected $_controller;

  protected $_action;

  protected $vars_export = array();

  protected $safe_actions = array();

  public function _setupController() {
    $this->config = Config::getInstance();

    $this->_controller = !empty($_GET[$this->config->getControllerName()]) ?
      $_GET[$this->config->getControllerName()] : $this->config->getDefaultAction();

    $this->vars_export['controller'] = $this->_controller;
    
    $this->_action = !empty($_GET[$this->config->getActionName()]) ?
      $_GET[$this->config->getActionName()] :
      $this->config->getDefaultAction();
    
    $this->vars_export['action'] = $this->_action; 
    $this->vars_export['web_path'] = $this->config->getStaticDir();
    $this->vars_export['url'] = Config::getProjectBaseUrl();
    $this->vars_export['title'] = \Config::PROJECT_NAME;

    $this->safe_actions[] = $this->config->getDefaultAction();
  }

  public function handleAction() {
    if (in_array($this->_action, $this->safe_actions)) {
      call_user_func(array($this, $this->_action));
    } else {
      Util::error_security("Unknown action or permission denied to execute.");
      die();
    }
  }

  /**
   * Renderize the view
   *
   * $view is the name of the view to render.
   * $arr_vars is an array of variables to export to be visible in the view file context.
   */
  public function render($view, $arr_vars = array()) {
    $view_file = $this->config->getViewDir() . DIRECTORY_SEPARATOR . $view . 'View.php';
    $safe_files = $this->config->getSafeFiles();

    if (in_array($view_file, $safe_files)) {
      extract($this->vars_export);
      extract($arr_vars);
      include $view_file;
    } else {
      Helper::throwPermissionDeniedInclude($view_file);
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
  public static $conn = null;
  protected $config;
  protected $_table_name;
  protected $_id_name = 'id';
  protected $_vo_name;

  public function __construct() {
    $this->_connect();
    $this->config = \Config::getInstance();
  }
  
  public function _connect() {
    self::$conn = Database::getConnection();
  }

  public function setTableName($tname) {
    $this->_table_name = $tname;
  }

  public function getTableName() {
    return $this->_table_name;
  }

  public function setValueObjectName($name) {
    $this->_vo_name = $name;
  }

  public function _setupDAO() {
    $this->_table_name = !empty($this->_table_name) ? $this->_table_name : strtolower(str_replace(__NAMESPACE__, "", str_replace('DAO', '', get_class($this))));
    $this->_vo_name = !empty($this->_vo_name) ? $this->_vo_name : ucfirst($this->_table_name);
  }

  public function get($id) {
    $this->_setupDAO();

    try {
      $stmt = self::$conn->prepare("SELECT * FROM " . $this->_table_name . " WHERE " . $this->_id_name . " = :id");
      $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
      $stmt->execute();

      $result = $stmt->fetchAll();

      if (count($result) > 0) {
        $r = $result[0];
        $voName = $this->_vo_name;
        $obj = new $voName();
        $obj = $this->map2object($obj, $r);

        return $obj;
      }

      return NULL;
      
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        die();
      } else {
        print "database error.\n";
        die();
      }
    }

    return NULL;
  }

  public function getAll() {
    $this->_setupDAO();
    
    try {
      $stmt = self::$conn->prepare("select * from " . $this->_table_name);
      $stmt->execute();

      $result = $stmt->fetchAll();
      $objects = array();

      for ($i = 0; $i < count($result); $i++) {
        $r = $result[$i];
        $voName = $this->_vo_name;
        $obj = new $voName();
        $obj = $this->map2object($obj, $r);
        $objects[] = $obj;
      }

      return $objects;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        die();
      }
    }

    return NULL;
  }

  public function update($obj) {
    $this->_setupDAO();

    try {
      $data = $obj->getData();
      $keys = array_keys($data);
      $sql = SQLBuilder::update($this->_table_name, $keys, array($this->_id_name));
      $stmt = self::$conn->prepare($sql);

      foreach($data as $name => &$val) {
        if ($name == $this->_id_name) {
          continue;
        }

        $stmt->bindParam(':' . $name, $val); 
      }
      
      $stmt->bindParam(':' . $this->_id_name, $data[$this->_id_name], \PDO::PARAM_INT);
      $stmt->execute();

      return $stmt->rowCount() > 0;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        die();
      }
    }

    return 0;
  }

  public function save($obj) {
    $this->_setupDAO();

    try {
      $data = $obj->getData();
      $keys = array_keys($data);
      $sql = SQLBuilder::insert($this->_table_name, $keys, $this->_id_name);
      $stmt = self::$conn->prepare($sql);

      foreach($data as $name => &$val) {
        if ($name == $this->_id_name) {
          continue;
        }

        $stmt->bindParam(':' . $name, $val);
      }
      
      $stmt->execute();

      return $stmt->rowCount() > 0;
    } catch (Exception $e) {
      if ($this->config->isDebug()) {
        print $e->getMessage();
        die();
      }
    }

    return 0;
  }

  public function map2object($user, $res) {
    $keys = array_keys($res);
    for ($j = 0; $j < count($keys); $j++) {
      if (is_string($keys[$j])) {
        $user->{$keys[$j]} = $res[$keys[$j]];
      }
    }

    return $user;
  }

  public function map2array($res) {
    $ar = array();
    $keys = array_keys($res);
    for ($j = 0; $j < count($keys); $j++) {
      if (is_string($keys[$j])) {
        $ar[] = array($keys[$j], $res[$keys[$j]]);
      }
    }

    return $ar;
  }
}

/**
 * Interface to Models
 */
interface IModel {
  
}

interface IValueObject {

}

abstract class AbstractValueObject implements IValueObject {
  protected $_data = array();

  public function __set($name, $val) {
    $this->_data[$name] = $val;
  }

  public function __get($name) {
    if (array_key_exists($name, $this->_data)) {
      return $this->_data[$name];
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
        return $this->_data[$prop];
      }
    } else if (preg_match('/^set/', $func) && count($args) == 1) {
      $prop = lcfirst(substr($func, 3));
      if (!empty($prop)) {
        $this->_data[$prop] = $args[0];
        return;
      }
    }
  }

  public function getData() {
    return $this->_data;
  }
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
    $config = Config::getInstance();
    try {
      $dbms = $config->getDbms();
      $host = $config->getDbHost();
      $user = $config->getDbUser();
      $pass = $config->getDbPass();
      $dbname = $config->getDbDatabase();
	
      $datasource = $dbms . ":" . "host=" . $host . ";dbname=" . $dbname;
      
      self::$conn = new \PDO($datasource, $user, $pass);
      self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); // Set Errorhandling to Exception

      return self::$conn;

    } catch (PDOException $e) {
      if ($config->isDebug()) {
        print "Database connection error.";
      } else {
        print "Database connection error: " . $e->getMessage();
      }
      die();
    } catch (Exception $e) {
      print "error: " . $e->getMessage();
      die();
    }    
  }

  public static function closeConnection() {
    self::$conn = null;
  }
}

class SQLBuilder {
  public static function update($table_name, $values, $where = NULL) {
    $sql = "UPDATE " . $table_name . " SET ";
    if (count($values) < 1) {
      return NULL;
    }

    $l = count($values);
    for ($i = 0; $i < $l; $i++) {
      $sql .= $values[$i] . " = :" . $values[$i];
      if ($i < ($l - 1)) {
        $sql .= ", ";
      }
    }

    if (!empty($where)) {
      $sql .= " WHERE ";
      $l = count($where);
      for ($i = 0; $i < $l; $i++) {
        $sql .= $where[$i] . " = :" . $where[$i];
        if ($i < ($l - 1)) {
          $sql .= " AND ";
        }
      }
    }

    return $sql;
  }

  public static function insert($table_name, $values, $primary_key = 'id') {
    $sql = "INSERT INTO " . $table_name;
    if (count($values) < 1) {
      return NULL;
    }

    $l = count($values);
    if ($l < 1) {
      return NULL;
    }

    $sql .= " (";
    
    for ($i = 0; $i < $l; $i++) {
      if ($values[$i] == $primary_key) {
        continue;
      }
      $sql .= $values[$i];
      if ($i < ($l - 1)) {
        $sql .= ", ";
      }
    }

    $sql .= ") VALUES (";

    for ($i = 0; $i < $l; $i++) {
      if ($values[$i] == $primary_key) {
        continue;
      }
      
      $sql .= ':' . $values[$i];
      if ($i < ($l - 1)) {
        $sql .= ", ";
      }
    }

    $sql .= ')';

    return $sql;
  }
}

final class Util {
  public static function error_security($text, $inc_html_header = true) {
    if ($inc_html_header) {
      Helper::print_html_header();      
    }

    $content = "";

    $content .= '<div style="border: 1px solid red; width: 600px; background-color: #ccc">';
    $content .= "SecPlus-PHP> Security prevention: ";
    $content .= htmlentities($text);
    $content .= "\n</div>";

    print $content;

    if ($inc_html_header) {
      Helper::print_html_footer();
    }
  }
}

/**
 * Helpers to aid in develop.
 */
final class Helper {
  /**
   * Returns a default doctype and html tag header.
   * @return string
   */
  public static function html_doctype() {
    return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
  }

  public static function html_footer() {
    return "</body></html>";
  }

  public static function print_html_footer() { print Helper::html_footer(); }

  /**
   * Print the default html and dcotype header to screen.
   * @return void
   */
  public static function print_html_doctype() { print SecPlus\Helper::html_doctype(); }

  /**
   * Return the dwfault html head tag and meta content-type and charset adjusted.
   * @var string
   * @return string
   */
  public static function html_header($charset = 'utf-8') {
    $c = \Config::getInstance();
    $html = Helper::html_doctype() . "\n";
    $html .= "<head>\n<meta http-equiv=\"Content-type\" content=\"text/html; charset=" . $charset . "\" />\n";
    $html .= "<title>" . $c::PROJECT_NAME . "</title>\n</head>\n<body>\n";
    return $html;
  }
  public static function print_html_header($charset = 'utf-8') { print Helper::html_header($charset); }
  public static function http_redirect($url) { header("Location: $url"); }
  public static function html_redirect($url, $time) { print '<meta http-equiv="refresh" content="' . htmlentities($time) . '; url=' . $url . '">'; }
  public static function alert($msg) { print '<script type="text/javascript">alert("' . $msg . '");</script>'; }
  public static function throwPermissionDeniedInclude($filename) {
    $c = Config::getInstance();
    if ($c->getEnvironment() == Config::ENV_DEVELOPMENT) {
      throw new \Exception('<span style="color: white; background-color: red;">File ' . htmlentities($filename) . ' not found or permission denied to include.</span><br><br>');
    } else {
      /* Blind error */
      throw new \Exception("<span style=\"color: white; background-color: red;\">Fatal Error!</span>");
    }
      die();
  }
}

interface IAuthController {
  public function permissionDenied();
}

abstract class AbstractAuthController
extends AbstractController
implements IAuthController {
  protected $session;
  protected $token = array();

  public function __construct() {
    session_start();
    $this->checkPermissions();
  }

  protected function checkPermissions() {
    if (empty($session)) {
      $this->permissionDenied();
    }
  }
}

final class Auth {
  public static function initSession($name = 'secplus_auth_session') {
    if (!empty($_SESSION)) {
      Auth::destroySession();
    }
    session_start();
    $_SESSION['session_start_at'] = time();
    $_SESSION[$name] = true;
  }

  public static function addSession($namevalue = array()) {
    foreach ($namevalue as $key => $value) {
      $_SESSION[$key] = $value;
    }
  }

  public static function hasSession($name) {
    return !empty($_SESSION) && !empty($_SESSION[$name]);
  }

  public static function destroySession() {
    session_destroy();
    unset($_SESSION);
  }
}

interface IShellCommand {
  public function help();
}

abstract class ShellCmd implements IShellCommand {
  /**
   * For future output customization.
   * @var string
   */
  public function print_status($text) {
    print $text;
  }
  
  public function print_success($text) {
    $t = "[+] " . $text .  "\n";
    print $t;
  }

  public function print_error($text) {
    $t = "[-] " . $text . "\n";
    print $t;
  }
}

class HelpCommand extends ShellCmd {
  public function auto() {
    $classes = get_declared_classes();

    foreach($classes as $c) {
      if (strstr($c, 'Command')) {
        $command = str_replace('Command', "", $c);
        $command = str_replace(__NAMESPACE__ . '\\', "", $command);
        $command = strtolower($command);
        $this->print_success($command);
      }
    }
  }

  public function help() {
    $this->auto;
  }
}

class CreateCommand extends ShellCmd {
  protected $config;
  protected $project_dir;
  protected $abstractModelClass = 'SecPlus\AbstractModel';
  
  public function __construct($config, $project_dir) {
    $this->config = $config;
    $this->project_dir = $project_dir;
  }

  public function help() {
    $this->print_status("create help:\n");
    $this->print_status("Command to generate scaffolding. With 'create' you could create\n");
    $this->print_status("CRUD's, model's, DAO's, VO's, unit-tests, etc.\n");
    $this->print_status("Usage: create <action> [<opt1> <opt2> ... <optN>]\n");
    $this->print_status("Actions:\n");
    $this->print_status("\tdao\tCreate new DAO.\n");
    $this->print_status("\t\tUsage: create dao <name-of-DAO> <name-of-table>\n");
    $this->print_status("\n");
     
  }

  public function dao($name, $tableName) {
    $voName = ucfirst($name);
    $daoName = $voName . 'DAO';
    $dao_src = "";

    $dao_tpl_fname = dirname($_SERVER['SCRIPT_FILENAME']) . '/tpl/dao.tpl';

    if (!file_exists($dao_tpl_fname)) {
      $this->print_error("SecPlus-PHP resource file '$dao_tpl_fname' not found.\naborting...");
      die();
    }

    $dao_tpl_content = file_get_contents($dao_tpl_fname);

    $dao_src = str_replace("{#vo_include#}",
                           str_replace($this->config->getRootProjectDir() . '/', "", $this->config->getVoDir() . '/' . $voName . '.php'),
                           $dao_tpl_content);

    $dao_src = str_replace('{#dao_name#}', $daoName, $dao_src);
    $dao_src = str_replace('{#model_extends#}', $this->abstractModelClass, $dao_src);
    
    $output = $this->project_dir . '/' . $this->config->getDaoDir() . '/' . $daoName . '.php';
    if (!file_put_contents($output, $dao_src)) {
      $this->print_error("[-] Failed to write on file '$output'");
      return;
    } else {
      $this->print_success("[+] DAO '$daoName' created with success.");
      $this->print_success("Output: $output");
    }
  }

  public function valueobject($vo_name) {
    $vo_name = ucfirst($vo_name);
    $vo_tpl_fname = dirname($_SERVER['SCRIPT_FILENAME']) . '/tpl/vo.tpl';
    $vo_src = file_get_contents($vo_tpl_fname);
    if (empty($vo_src)) {
      $this->print_error("Failed to open the SecPlus-PHP resource file '$vo_tpl_fname'.");
      return;
    }
    $vo_src = str_replace("{#value_object#}", $vo_name, $vo_src);

    $output = $this->project_dir . '/' . $this->config->getVoDir() . '/' . $vo_name . '.php';    

    if (!file_put_contents($output, $vo_src)) {
      $this->print_error("[-] Failed to write on file '$output'");
      return;
    } else {
      $this->print_success("[+] ValueObject '$vo_name' created with success.");
      $this->print_success("Output: $output");
    }
  }

  public function vo($vo_name) {
    $this->valueobject($vo_name);
  }
}

class Shell {
  protected $prompt = "SEC+> ";
  protected $config;
  protected $config_file = 'config.php';

  public function __construct($config_file) {
    $this->config_file = $config_file;
    $this->banner();
    $this->checkConfig();
  }

  public function checkConfig() {
    if (file_exists($this->config_file)) {
      require $this->config_file;
      if (class_exists('\Config')) {
        $this->config = \Config::getInstance();
        print "[+] using '{$this->config_file}' for configuration.\n";
      }
    }
  }

  public function loopExecute() {
    while(1) {
      print $this->prompt;
      $command = Shell::readInput();
      $this->execute($command);
    }
  }

  public static function readInput() {
    return str_replace(array("\r","\n"), null, fread(STDIN, 1024));
  }

  public function execute($command) {
    $params = explode(" ", $command);
    
    $classname = __NAMESPACE__ . '\\' . $params[0] . 'Command';
    $main_command = $params[0];
    $action = "";
    $p = array();

    if (count($params) > 1) {
      $action = $params[1];
    } else {
      /* for commands that does not have a action. eg. help */
      $action = "auto";
    }

    if (count($params) > 2) {
      $p = array_slice($params, 2);
    }

    if (!class_exists($classname)) {
      print "error: command '$main_command' not found.\n";
      print "type 'help' for a list of commands available.\n";
      return;
    }
    
    $class = new $classname($this->config, dirname($this->config_file));

    if (!method_exists($class, $action)) {      
      print "[-] error: command '$main_command' does not have a action '$action'.\n";
      print "[+] for help, type: $main_command help.\n";
      return;
    }
    call_user_func_array(array($class, $action), $p);
  }

  public function banner() {
    $b = "SEC+ Security WebFramework\nLicense:\tGNU GPL v2.0\nAuthor:\tTiago Natel de Moura aka i4k <tiago4orion@gmail.com>\n";
    $b .= php_uname() . "\n";
    print($b);
  }

  public static function write($str) {
    fwrite(STDOUT, $str, strlen($str));
  }
}

if (php_sapi_name() == "cli") {
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
