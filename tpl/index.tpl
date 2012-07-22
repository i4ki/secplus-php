<?php

require('{#secplus_framework_path#}');
require('{#config_file#}');

try {
  $config = \Config::getInstance();
  $web = new SecPlus\WebFramework($config);
 } catch (Exception $e) {
   print $e->getMessage();
}

