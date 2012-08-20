<?php
/** #FAKE404# */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) {
  header("HTTP/1.1 404 Not Found", true);
  $response = "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL " . $_SERVER['REQUEST_URI'] . " was not found on this server.</p>\n<hr>\n<address>SecPlus-PHP</address>\n</body></html>";
  print($response);
  die();
}

?>
