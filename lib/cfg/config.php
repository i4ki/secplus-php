<?php

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

  const CSRF_NONE = 0;
  const CSRF_BASIC = 1;
  const CSRF_PARANOID = 2;

  /**
   * Singleton configuration instance
   * @param Config
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
   * @param string
   */
  protected $dbHost = "127.0.0.1";

  /**
   * Database user
   * @param string
   */
  protected $dbUser = "";

  /**
   * Database password
   * @param string
   */
  protected $dbPass = "";

  /**
   * Database driver
   * @param string
   */
  protected $dbms = "mysql";

  /**
   * Database name
   * @param string
   */
  protected $dbDatabase = "";

  /**
   * Salt for hash algorithms
   * @param string
   */
  protected $salt = "Welcome, to the desert of the real";

  /**
   * Default PHP Session Name
   * @param 
   */
  protected $session = 'SecPlus-PHP';

  /**
   * Level of CSRF protection (CSRF_NONE = None, CSRF_BASIC = Basic, CSRF_PARANOID = Paranoid)
   * @param int
   */
  protected $csrfLevel = Config::CSRF_NONE;

  /**
   * Path where image files should be uploaded to
   * @param string
   */
  protected $imageUploadPath = "user_content/img/";
  
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
   & @param array
  */
  protected $safeFiles = array();

  protected $library = array(
                             'Database' => 'lib/db/database.php',
                             'Util' => 'lib/core/util.php',
                             'Helper' => 'lib/hlp/Helper.php',
                             'SQLBuilder' => 'lib/db/sqlbuilder.php',
                             'Auth' => 'lib/core/auth.php',
                             'AbstractAuthController' => 'lib/core/auth.php',
                             'AbstractController' => 'lib/core/controller.php',
                             'ValueObject' => 'lib/core/valueobject.php',
                             'XSS' => 'lib/sec/xss.php',
                             'CSRF' => 'lib/sec/csrf.php',
                             'Upload' => 'lib/sec/upload.php'
                             );

  /**
   * Safe PHP properties that can be used with __call.
   * This prevent unrestricted php code execution.
   * @param array
   */
  protected $safeProperties = array(
                                    'rootProjectDir','libDir','controllerDir',
                                    'modelDir','daoDir','voDir','viewDir','staticDir',
                                    'dbHost','dbUser','dbPass','dbDatabase','dbms',
                                    'salt','controllerName','actionName','safeFiles',
                                    'defaultController', 'defaultAction', 'defaultTitle',
                                    'session', 'library', 'csrfLevel', 'imageUploadPath'
                                    );

  /**
   * Default controller to be called in case of anyone controller specified
   * in the URL.
   * @param string
   */
  protected $defaultController = "home";

  /**
   * Default action to be called in case of anyone action specified in
   * the URL.
   * @param string
   */
  protected $defaultAction = "view";

  /**
   * MVC Configuration
   */

  /**
   * Name of the controllers.
   * This is the name of the uri parameter that invoke the controller.
   * ex.: http://site]/?[controllerName]=home
   * @param string
   */
  protected $controllerName = "controller";

  /**
   * Name of the action.
   * @param string
   */
  protected $actionName = "action";

  /**
   * Default title for pages
   * @param string
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
    $this->rootProjectDir = basename($_SERVER['SCRIPT_FILENAME']) == 'index.php' ? dirname($_SERVER['SCRIPT_FILENAME']) : '.';
    $this->libDir = $this->rootProjectDir . '/lib';
    $this->controllerDir = $this->rootProjectDir . '/controller';
    $this->modelDir = $this->rootProjectDir . '/model';
    $this->daoDir = $this->modelDir . '/dao';
    $this->voDir = $this->modelDir . '/vo';
    $this->viewDir = $this->rootProjectDir . '/view';
    $this->staticDir = dirname($this->getProjectUrl()) . '/static';
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

  public static function getFrameworkRootDir() {
    return WebFramework::getRootDir();
  }
}
