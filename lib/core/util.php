<?php

namespace SecPlus;

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
