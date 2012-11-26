<?php

namespace SecPlus;

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
   * Return the default html head tag and meta content-type and charset adjusted.
   * @param string
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
  public static function html_redirect($url, $time = 1) { print '<meta http-equiv="refresh" content="' . htmlentities($time) . '; url=' . $url . '">'; }  
  public static function html_redirect2controller($controller, $action = NULL) {
    $cfg = Config::getInstance();
    self::html_redirect(Config::getProjectBaseUrl() . '/?' . $cfg->getControllerName() . '=' . $controller . '&' . ($action == NULL ? "" : Config::getActionName() . '=' . $action));
  }
  
  public static function js_back() { print "<script type=\"text/javascript\">history.back(-1);</script>"; }
  public static function alert($msg) { print '<script type="text/javascript">alert("' . $msg . '");</script>'; }

  public static function safe_print($txt) {
    $rootFramework = \Config::getFrameworkRootDir();
    require $rootFramework . '/lib/sec/xss.php';
    

    $txt = XSS::txt($txt);

    print $txt;    
  }
  
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

  public static function throwPermissionDeniedMethod($method) {
    $c = Config::getInstance();
    if ($c->getEnvironment() == Config::ENV_DEVELOPMENT) {
      throw new \Exception('<span style="color: white; background-color: red;">Column name ' . htmlentities($method) . ' not found or permission denied to access.</span><br><br>');
    } else {
      /* Blind error */
      throw new \Exception("<span style=\"color: white; background-color: red;\">Fatal Error!</span>");
    }
    die();
  }
}
