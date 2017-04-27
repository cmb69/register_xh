<?php

/**
 * Captcha image functions of Register_XH.
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2017 Christoph M. Becker
 */

// Functions md5_encrypt, md5_decrypt and generateCaptchaImage taken from genizguestbook


if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}


/*
 * Check for GD Extension to allow Captcha image generation
 */
function checkGD() {
  $CheckGD = get_extension_funcs("gd");
  if(!$CheckGD) {
    $o = "<b>'$plugin' plugin Error :</b>\n" .
      "GD image library not installed!<br><br>\n" .
      "<b>Solution:</b>\n" .
      "Your server does not meet the requirements for the " .
      "'$plugin' plugin, install the GD lib";
    return $o;
    exit;
  }
}


/*
 *  Encrypt a string using a specific key.
 */
function md5_encrypt($s,$key) {
  $r="";
  for($i=0;$i<strlen($s);$i++) {
    $r .= substr(str_shuffle(md5($key)),($i % strlen(md5($key))),1) . $s[$i];
  }
  for($i=0;$i<strlen($r);$i++)
    $s[$i] = chr(ord($r[$i]) + ord(substr(md5($key),($i % strlen(md5($key))),1)));
  return urlencode(base64_encode($s));
}


/*
 *  Decrypt a string using a specific key.
 */
function md5_decrypt($s,$key) {
  $r = "";
  $s = base64_decode(urldecode($s));
  for($i=0;$i<strlen($s);$i++)
    $s[$i] = chr(ord($s[$i])-ord(substr(md5($key),($i % strlen(md5($key))),1)));
  for($i=1;$i<strlen($s);$i=$i+2) $r.=$s[$i];
  return $r;
}


/*
 *  Create a captcha image.
 *  str:      string to display as image
 *  imgW:     width of image
 *  imgH:     height of image
 *  key:      key to encrypt code
 *  chars:    number of characters to display of string
 *  font:     name and path to font (TTF)
 *  crypt:    string to use for encryption
 */
function generateCaptchaImage($str, $imgW, $imgH, $chars, $font, $crypt) {
  $plugin = basename(dirname(__FILE__),"/");

  $imgX = 5;
  $imgY = $imgH - 5;
  $image = imagecreatetruecolor($imgW, $imgH);
  imageantialias($image,true);
  $backgr_col = imagecolorallocate($image,255,255,255);
//  $backgr_col = imagecolortransparent($image, $backgr_col);
  $border_col = imagecolorallocate($image,208,208,208);
  imagefilledrectangle($image,0,0,$imgW,$imgH, $backgr_col);
  imagefilledrectangle($image,
    rand(5, $imgW/4),rand(5, $imgH/4),
    rand($imgW/4+5,$imgW/2+5),rand($imgH/4+5,$imgH/2+5),$border_col);
  $str = md5_decrypt($str,$crypt);
  for ($i=0;$i<$chars;$i++) {
    $font_size = rand($imgH/4+5, $imgH/2+2);
    $angle = rand(-20, 20);
    $charwidth = $imgW / $chars;
    $x = (int)($imgX+(rand(($i*0.8*$charwidth),($i*0.9*$charwidth))));
    $y = (int)($imgY/rand(1.7,1.99));
    $text_col = imagecolorallocate($image,rand(0,100),rand(50,80),rand(20,80));
    imagettftext($image,$font_size,$angle,$x,$y,$text_col,$font,substr($str,$i,1));
  }
  header("Content-type: image/png");
  imagepng($image);
  imagedestroy ($image);
}


/*
 *  Generate random code for captcha function.
 */
function generateRandomCode($length) {
  $str="";
  for ($i = 0; $i < $length; $i++) {
    $str .= chr(rand(97, 122));
  }
  return $str;
}


/*
 *  Generate random formula to be calculated by user instead of captcha image.
 */
function generateCaptchaFormula($length) {
  $str="";
  for ($i = 0; $i < $length; $i++) {
    if($str == "")
      $str .= rand(1, 9);
    else
      $str .= " + " . rand(1, 9);
  }
  return $str;
}


/*
 *  Get HTML code to insert "captcha" image.
 *  code:   code to display as image or text depending on mode
 *  action: action to use
 *  imgW:   width of image
 *  imgH:   height of image
 *  key:    key to encrypt code
 *  mode:   mode to use
 *          - none:    no captcha usage
 *          - formula: display just mathematical formula as text
 *          - image:   display image with captcha code
 */
function getCaptchaHtml($action, $code, $imgW, $imgH, $key, $mode) {
  $captcha = "";
  if ($mode == "image" && function_exists('imagecreatetruecolor')) {
    $encCode = md5_encrypt( strtoupper($code) , $key);
    $captcha = tag('img src="' . sv('REQUEST_URI') . "&amp;" . 'action=' . $action . '&amp;captcha=' . $encCode . '&amp;ip=' . $_SERVER['REMOTE_ADDR'] . '" ' . 'alt="Verify Image" ' . 'width="' . $imgW . '" height="' . $imgH . '"');
    return $captcha;
  } elseif($mode == "formula") {
    return htmlspecialchars($code, ENT_COMPAT, 'UTF-8');
  } else
    return "";
}


define("CAPTCHA_LOADED", '1.2');

?>
