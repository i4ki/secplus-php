<?php

namespace SecPlus;
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

  protected $csrf_protected = array();

  public function __construct() {
    $this->_setupController();
  }

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

      if(in_array($this->_action, $this->csrf_protected) && !SecPlus\CSRF::verify()){
        Util::error_security("Not a valid request.");
        die();        
      }

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

    if (in_array($view_file, $safe_files) && file_exists($view_file)) {
      extract($this->vars_export);
      extract($arr_vars);
      include_once($view_file);
    } else {
      Helper::throwPermissionDeniedInclude(basename($view_file));
    }
  }
}
