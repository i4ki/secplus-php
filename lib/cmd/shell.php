<?php

namespace SecPlus;

interface IShellCommand {
  public function help();
}


/**
 * SecPLus-PHP Interactive mode.
 * Scaffolding commands
 */
abstract class ShellCmd implements IShellCommand {
  protected $config;
  protected $config_file;
  protected $project_dir;

  public function setConfig($c) {
    $this->config = $c;
  }

  public function setConfigFile($c) {
    $this->config_file = $c;
    $this->project_dir = dirname($this->config_file);
  }
  
  /**
   * For future output customization.
   * @var string
   */
  public function print_status($text) {
    print $text;
  }

  public function print_statusln($text) {
    print $text . "\n";
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

class StatusCommand extends ShellCmd {  
  public function auto() {
    $this->stat_configuration();
  }

  public function stat_configuration() {
    $this->print_statusln("Config file: \t\t" . $this->config_file);
    $this->print_statusln("SecPlus-PHP directory: \t" . $this->config->getRootProjectDir());
    $this->print_statusln("Library Dir: \t\t" . $this->config->getLibDir());
    $this->print_statusln("Model directory: \t" . $this->config->getModelDir());
    $this->print_statusln("View directory: \t" . $this->config->getViewDir());
    $this->print_statusln("Controller directory: \t" . $this->config->getControllerDir());
    $this->print_statusln("Value Objects dir: \t". $this->config->getVoDir());
    $this->print_statusln("DAO directory: \t\t" . $this->config->getDaoDir());
  }

  /** TODO **/
  public function view() {
    $countFiles = 0;
    $viewDir = $this->config->getViewDir();
    $valids = $this->check_files($viewDir);
    $countFiles = count($valids);

    $this->print_success("Found $countFiles valid views.\n");
  }

  public function controller() {
    $controllerDir = $this->config->getControllerDir();
    $valids = $this->check_files($controllerDir);
    $countFiles = count($valids);

    $this->print_success("Found $countFiles valid controllers.\n");
  }
  
  public function check_files($directory) {
    $path2delete = array();
    $path2delete2 = array();
    $path2fix = array();
    $vdir = $directory;
    $iterator = Shell::getFiles($vdir);
    $files = array();
    foreach ($iterator as $path) {
      if ($path->isDir()) {
        continue;
      } else if (in_array($path->__toString(), $this->config->getSafeFiles())) {
        $files[] = $path->__toString();
        if (preg_match("/php$/", $path->__toString())) {
          $content = file_get_contents($path->__toString());
          if (preg_match('/header\(\"HTTP\/1\.1 404 Not Found/', $content)) {
            $this->print_success($path->__toString());
          } else {
            $this->print_status("[!][WARN][UNRESTRICTED ACCESS][" . $path->__toString() . "]\n");
            $path2fix[] = $path->__toString();
          }  
        } else {
          $this->print_status("[!] WARN: INSECURE EXTENSION: ");
          $this->print_statusln("[".$path->__toString()."]");
          $path2delete[] = $path->__toString();
        }
      } else {
        $this->print_status("[!] WARN: UNSAFE FILE: " . $path->__toString() . "]\n");
        $path2delete2[] = $path->__toString();
      }
    }

    if (count($path2delete) > 0) {
      $this->print_status("\n[INSECURE EXTENSION] Some files doesn't have '.php' extension, this could be a security problem if the directory of this files are inside the document root of the web server. Any attacker can execute this files and compromise the confidentiality of your application. By default, most webservers show the contents of files with unknown extensions as plain text.\n");
      $this->print_status("Do you want delete this files? [y/N] ");
      $resp = Shell::readInput(false);
      if ($resp == 'Y' || $resp == 'y') {
        Shell::deleteFiles($path2delete);
      }
    }

    if (count($path2fix) > 0) {
      $this->print_status("\n[UNRESTRICTED ACCESS] It's a security problem not restrict access to internal objects like Models, Views, Controllers, DAOs, etc. The majority of this files could leak usefull information for attackers. This flaw is called 'Insecure Direct Object Reference' and you can obtain more information in the link below: \nhttps://www.owasp.org/index.php/Top_10_2010-A4-Insecure_Direct_Object_References\nThis is a very simple check to see if the file returns a 404 HTTP response in case of directly accessed. If you know that the files warned above are safe, doesn't worry about the alert.\n");
    
      $this->print_status("Do you want fix this problems? [y/N] ");
      $resp = Shell::readInput(false);
      if ($resp == 'Y' || $resp == 'y') {
        foreach ($path2fix as $p) {
          $this->print_success("fixing '" . $p . "'");
          $tpl_fake_404 = dirname(__FILE__) . '/tpl/fake_404.tpl';
          if (!file_exists($tpl_fake_404)) {
            $this->print_error("SecPLus-PHP Resource file not found (" . $tpl_fake_404 . ").\n");
            die();
          }
        
          $content = file_get_contents($p);
          $new_content = file_get_contents($tpl_fake_404) . "\n" . $content;
          if (file_put_contents($p, $new_content) == FALSE) {
            $this->print_error("Permission denied to fix the file '" . $p . "'");
          }
        }
      }
    }

    if (count($path2delete2) > 0) {
      $this->print_status("\n[UNSAFE FILE] Every model, view and controller file of your project NEED be referenced in the safe_files property of configuration file. If this is not the case, your application could be affected by 'Insecure Direct Object Reference' flaw and the attacker could compromise the confidentiality of your application. It's extremely recommended to verify the UNSAFE FILES listed above and add them to the safe_files in the correct case. \nIf you want more information of this kind of vulnerability, follow the link below:\nhttps://www.owasp.org/index.php/Top_10_2010-A4-Insecure_Direct_Object_References\nYou want *PERMANENTLY DELETE* the *unsafe* files? [y/N] ");
      $resp = Shell::readInput(false);
      if ($resp == 'Y' || $resp == 'y') {
        Shell::deleteFiles($path2delete2);
      }
    }

    return $files;
  }

  public function help() {
    $this->print_status("Status help:\n");
    $this->print_status("This command will show information about the project, like controllers, models, views. etc.\n");
    $this->print_status("Actions:\n");
    $this->print_status("\tcontroller\n");
    $this->print_status("\tview\n");
    $this->print_status("\tmodel\n");
    $this->print_status("\tconfig\n");
  }
  
  public function status() {
    $this->auto();
  }
}

class CreateCommand extends ShellCmd {
  protected $abstractModelClass = 'SecPlus\AbstractModel';
  
  public function help() {
    $this->print_status("create help:\n");
    $this->print_status("Command to generate scaffolding. With 'create' you could create\n");
    $this->print_status("CRUD's, model's, DAO's, VO's, unit-tests, etc.\n");
    $this->print_status("Usage: create <action> [<opt1> <opt2> ... <optN>]\n");
    $this->print_status("Actions:\n");
    $this->print_status("\tproject\t\tCreate new project. Based on Configuration file.\n");
    $this->print_status("\t\tUsage: create project\n\n");

    $this->print_status("\tdao\t\tCreate new Controller.\n");
    $this->print_status("\t\tUsage: create controller <name-of-Controller> [,<tpl-file>]\n\n");
    
    $this->print_status("\tdao\t\tCreate new DAO.\n");
    $this->print_status("\t\tUsage: create dao <name-of-DAO> [,<name-of-table>]\n\n");
    $this->print_status("\tvalueobject\tCreate new ValueObject.\n");
    $this->print_status("\t\tUsage: create valueobject <name-of-vo>\n\n");
    $this->print_status("\tconfigfile\tCreate new Configuration File.\n");
    $this->print_status("\t\tUsage: create configfile [,<output-path>]\n\n");
    $this->print_status("\tindex\t\tCreate new index PHP file.\n");
    $this->print_status("\t\tUsage: create index [,<output-path>]\n\n");
    $this->print_status("\tdirectories\tCreate new project directories.\n");
    $this->print_status("\t\tUsage: create directories\n\n");
    $this->print_status("\n");     
  }

  public function project() {
    $this->index();
    $this->directories();
    $this->controller('home');
    $this->view('home');
  }

  public function page($name, $template = NULL) {
    $this->controller($name);
    $this->view($name, $template);
  }

  public function createDir($dirname) {
    if (file_exists($dirname)) {
      $this->print_error("file '$dirname' already exists...");
      return FALSE;
    }

    if (mkdir($dirname, 0777, true)) {
      $this->print_success("[CREATED] " . $dirname);
      return TRUE;
    } else {
      $this->print_error("[ERROR] " . $dirname);
      return FALSE;
    }
  }

  public function directories() {
    $this->createDir($this->config->getLibDir());
    $this->createDir($this->config->getControllerDir());
    $this->createDir($this->config->getViewDir());
    $this->createDir($this->config->getModelDir());
    $this->createDir($this->config->getDaoDir());
    $this->createDir($this->config->getVoDir());    
  }

  public function controller($cname, $tpl_file = NULL) {
    $controllerName = ucfirst($cname);
    $control_tpl = !empty($tpl_file) ? $tpl_file : dirname($_SERVER['SCRIPT_FILENAME']) . '/tpl/controller.tpl';

    if (empty($cname)) {
      $this->print_error("Controller needs a name!\n");
      return;
    }

    if (!file_exists($control_tpl)) {
      $this->print_error("SecPlus-PHP resource file '$control_tpl' not found.\naborting...");
      die();
    }

    $control_src = file_get_contents($control_tpl);

    if (empty($control_src)) {
      $this->print_error("Permission denied to open '$control_tpl'.");
      return;
    }

    if ($tpl_file != NULL) {
      $control_src = preg_replace("/class ([a-zA-Z_]+)Controller/", "class ${controllerName}Controller", $control_src);
    } else {
      $control_src = str_replace("{#controller_name#}", $controllerName, $control_src);
    }

    $output = $this->project_dir . '/' . $this->config->getControllerDir() . '/' . $controllerName . 'Controller.php';    
    
    if (!file_put_contents($output, $control_src)) {
      $this->print_error("[-] Failed to write on file '$output'");
      return;
    } else {
      $this->print_success("Controller '{$controllerName}Controller created with success.");
      $this->print_success("Output: $output");
    } 
  }

  /**
   * Create new View file.
   * @param $name String
   * @param $tpl_file String pathname
   * @return void
   */
  public function view($name, $tpl_file = NULL) {
    $viewName = ucfirst($name);
    $tpl_fake_404 = dirname($_SERVER['SCRIPT_FILENAME']) . '/tpl/fake_404.tpl';
    $view_tpl = !empty($tpl_file) ? $tpl_file : dirname($_SERVER['SCRIPT_FILENAME']) . '/tpl/view.tpl';

    if (!file_exists($view_tpl) || !file_exists($tpl_fake_404)) {
      $this->print_error("SecPlus-PHP resource files not found.\naborting...");
      die();
    }

    $output = $this->project_dir . '/' . $this->config->getViewDir() . '/' . $viewName . 'View.php';

    $this->print_status("Creating new view.\n");
    $this->print_status("Properties:\n");
    $this->print_status("\tName: " . $viewName . "View\n");
    $this->print_status("\tTemplate file: " . $view_tpl . "\n");
    $this->print_status("\tOutput file: " . $output . "\n");

    $this->print_status("Create this view? [y/N] ");
    $resp = Shell::readInput(false);

    if ($resp != 'y' && $resp != 'Y') {
      return;
    }

    $view_src = file_get_contents($view_tpl);
    if (!preg_match('/header\(\"HTTP\/1\.1 404 Not Found/', $view_src)) {
      $view_src = file_get_contents($tpl_fake_404) . "\n\n" . $view_src;
    }

    $view_src = str_replace("{#view_name#}", $viewName, $view_src);

    if (empty($view_src)) {
      $this->print_error("Permission denied to open '$view_tpl'.");
      return;
    }
    
    if (!file_put_contents($output, $view_src)) {
      $this->print_error("Failed to write on file '$output'");
      return;
    } else {
      $this->print_success("View '{$viewName}View created with success.");
      $this->print_success("Output: $output");
    } 
  }

  public function index() {
    $index_file = dirname($_SERVER['SCRIPT_FILENAME']) . '/tpl/index.tpl';
    if (!file_exists($index_file)) {
      $this->print_error("SecPlus-PHP resource file '$index_file' not found.\naborting...");
      die();
    }

    $index_src = file_get_contents($index_file);
    $secplus_path = $_SERVER['SCRIPT_FILENAME'];
    $index_src = str_replace("{#secplus_framework_path#}", $secplus_path, $index_src);
    $index_src = str_replace("{#config_file#}", $this->config_file, $index_src);

    $output = $this->project_dir . '/index.php';
    if (!file_put_contents($output, $index_src)) {
      $this->print_error("Failed to write on file '$output'");
      return;
    } else {
      $this->print_success("Index created with success.");
      $this->print_success("Output: $output");
    }
  }

  public function dao($name, $tableName = "") {
    if (empty($name)) {
      $this->print_error("argument name not supplied.");
      $this->help();
      return;
    }
    
    $voName = ucfirst($name);
    $daoName = $voName . 'DAO';
    $dao_src = "";

    $dao_tpl_fake_404 = dirname($_SERVER['SCRIPT_FILENAME']) . '/tpl/fake_404.tpl';
    $dao_tpl_fname = dirname($_SERVER['SCRIPT_FILENAME']) . '/tpl/dao.tpl';

    if (!file_exists($dao_tpl_fname) || !file_exists($dao_tpl_fake_404)) {
      $this->print_error("SecPlus-PHP resource files not found.\naborting...");
      die();
    }

    $dao_tpl_fake = file_get_contents($dao_tpl_fake_404);
    $dao_tpl_content = $dao_tpl_fake . "\n\n" . file_get_contents($dao_tpl_fname);

    $dao_src = str_replace("{#vo_include#}",
                           str_replace($this->config->getRootProjectDir() . '/', "", $this->config->getVoDir() . '/' . $voName . '.php'),
                           $dao_tpl_content);

    $dao_src = str_replace('{#dao_name#}', $daoName, $dao_src);
    $dao_src = str_replace('{#model_extends#}', $this->abstractModelClass, $dao_src);
    
    $dao_src = str_replace('{#table_name#}', !empty($tableName) ? 'protected $_tableName = "' . $tableName . '";' : "", $dao_src);
    
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

  public static function configfile($config_file = 'config.php') {
    $conf_tpl_fname = dirname($_SERVER['SCRIPT_FILENAME']) . '/tpl/configfile.tpl';
    $conf_src = file_get_contents($conf_tpl_fname);

    if (empty($conf_src)) {
      print("[-] Failed to open SecPlus-PHP resource file '{$conf_tpl_fname}'.\n");
      return FALSE;
    }

    $output = $config_file;

    if (!file_put_contents($output, $conf_src)) {
      $this->print_error("[-] Failed to write on file '$output'\n");
      return FALSE;
    } else {
      print("[+] Config file '$config_file' created with success.\n");
      print("[+] Output: $output\n");
      return TRUE;
    }
  }

  public function vo($vo_name) {
    $this->valueobject($vo_name);
  }
}

class ExitCommand extends ShellCmd {
  public function help() {}
  public function auto() {
    $this->print_success("Exiting...");
    if (function_exists('readline_write_history')) {
      $history = dirname($_SERVER['SCRIPT_FILENAME']) . '/.secplus_history';
      readline_write_history($history);
    }

    exit(0);
  }
}

class MysqlCommand extends ShellCmd {
  protected $terminal = array('xterm -e', 'gnome-terminal -e', 'cmd.exe /c');
  public function help() {
    $this->print_status("Execute your MySQL Client\n");
    $this->print_status("By default, this command start a new gnome-terminal window with mysql client automatically connected in the database of your project (obtained from config file).\n");
    $this->print_status("SEC+> mysql\n");
    $this->print_status("Options:\n");
    foreach ($this->terminal as $i => $v) {
      $this->print_status("$i\t$v\n");
    }

  }
  public function auto() {
    $default_term = 0; // xterm
    $exec_functions = array('exec', 'system', 'passthru', 'popen');
    $mysql_exec = "mysql -u" . $this->config->getDbUser() . " -p" . $this->config->getDbPass() . " " . $this->config->getDbDatabase();
    $resp = NULL;

    $this->print_status("Terminal windows available:\n");
    foreach ($this->terminal as $i => $v) {
      $this->print_status("$i\t$v\n");
    }
    while (!is_int($resp)) {
      $this->print_status("Choice your terminal: [$default_term] ");
      $resp = Shell::readInput(false);
      if (empty($resp)) {
        $resp = $default_term;
      } else {
        $resp = intval($resp);
      }
    }

    foreach ($exec_functions as $f) {
      if (function_exists($f)) {
        $command = $this->terminal[$resp] . " \"" . $mysql_exec . "\"";
        $this->print_status("forking process: " . $this->terminal[$resp] . "\n");
        $pid = pcntl_fork();
        if ($pid == -1) {
          die('could not fork');
        } else if ($pid) {
          // parent, return to the SEC+ shell
          return;
        } else {
          // child
          $f($command);
          return;
        }
        
      }
    }

    $this->print_status("Your php.ini has disabled unsafe functions related to command execution.\n");
  }
}

class Shell {
  const prompt = "SEC+> ";
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
    } else {
      print "[-] file '{$this->config_file}' not exists or permission denied to open.\n";
      print "[-] we need a configuration file to run scaffolding commands.\n";
      print "[?] You want that we generate the '{$this->config_file}' for you? [y/N] ";
      $opt = Shell::readInput(false);
      if (empty($opt)||($opt != "y" && $opt != "Y")) {
        print "[-] aborting...\n";
        die();
      } else {
        if ($this->generateConfig($this->config_file)) {
          $this->checkConfig();
        } else {
          die();
        }
      }      
    }
  }

  public function generateConfig($config_file) {
    return CreateCommand::configfile($config_file);
  }

  public function loopExecute() {
    if (function_exists('readline_read_history')) {
      readline_read_history(dirname($_SERVER['SCRIPT_FILENAME']) . '/.secplus_history');
    }

    while(1) {
      $command = Shell::readInput();
      $this->execute($command);
    }
  }

  public static function readInput($with_prompt = true) {
    $str = "";
    if (function_exists("readline")) {
      $str = readline($with_prompt == true ? Shell::prompt : "");
      readline_add_history($str);
    } else {
      print Shell::prompt;
      $str = str_replace(array("\r","\n"), null, fread(STDIN, 1024));
    }

    return $str;
  }

  public function execute($command) {
    /* readline returns FALSE for CTRL+D */
    if ($command == FALSE) {
      print "\n";
      $exit = new ExitCommand();
      $exit->auto();
      return;
    }
    
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
    $class->setConfig($this->config);
    $class->setConfigFile($this->config_file);

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

  public static function getFiles($dir) {
    return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir),
                                          \RecursiveIteratorIterator::CHILD_FIRST);
  }

  public static function deleteFiles($paths = array()) {
    foreach ($paths as $p) {
      print "Deleting '" . $p . "'\n";
      if (unlink($p) == FALSE) {
        print "Failed to delete file '" . $p . "'\n";
      }
    }
  }
}

