<?php

function strip_last_slash($str) {
  return substr($str,-1,1) == '/' ? substr($str,0,strlen($str)-1) : $str;
}

function myescapeshellarg($str) {
    return "'" . $str . "'";
    //return "'" . str_replace("'", "'\"'\"'", $str) . "'";
}