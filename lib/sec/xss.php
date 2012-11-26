<?php

namespace SecPlus;

class XSS {
  const BASIC = 0x01;
  public static function txt($txt, $type = XSS::BASIC) {
    if ($type == XSS::BASIC) {
       return htmlentities($txt);
    }
  }
}
