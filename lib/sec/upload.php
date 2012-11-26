<?php

namespace SecPlus;

class Upload {

  public static function upload_image_file($file) {

    $config = Config::getInstance();
    $base_path = $config->getImageUploadPath();

	  if (preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $file["name"], $ext)){
      $filename = md5(uniqid(time())) . "." . $ext[1];
	    $filepath = $base_path . $filename;
	    move_uploaded_file($file["tmp_name"], $filepath);
      return $filename;
    } else {
      Util::error_security("File type not allowed: " . $file["name"]);
      exit();
    }
  }
}
