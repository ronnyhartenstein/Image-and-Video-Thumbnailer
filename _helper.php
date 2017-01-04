<?php

/*
 * Helper functions
 */

function notify($message, $subtitle='') {
  global $argv;
  $cmd = dirname($argv[0]).'/Grunt-notify.app/Contents/MacOS/Grunt -message "'.$message.'"'
    . ' -title "NEF-Thumbnailer" -subtitle "'.$subtitle.'"'
    . ' -group "NEF-thumbnailer"';
  exec($cmd);
}

$logfile_name = dirname($argv[0]).'/'.basename($argv[0],'.php').'_lastrun.log';
if (file_exists($logfile_name)) {
  unlink($logfile_name);
}
function logfile($level, $msg) {
  global $logfile_name;
  file_put_contents($logfile_name, date('Y-m-d H:i:s')." ${level} ${msg}");
}

function log_error($msg) {
  // echo "ERROR: $msg\n";
  notify($msg, "FEHLER");
  trigger_error($msg, E_USER_ERROR);
  logfile("ERROR", $msg);
}

function log_warning($msg) {
  // echo "WARN: $msg\n";
  notify($msg, "WARNUNG");
  trigger_error($msg, E_USER_WARNING);
  logfile("WARN", $msg);
}

function log_debug($msg) {
  global $debug;
  if ($debug) {
    echo "DEBUG: $msg\n";
    logfile("DEBUG", $msg);
  }
//  trigger_error($msg, E_USER_NOTICE);
}

function log_info($msg) {
  if (ini_get('error_reporting') & E_NOTICE) {
    notify($msg);
    echo "INFO: $msg\n";
    logfile("INFO", $msg);
  }
//  trigger_error($msg, E_USER_NOTICE);
}

function strip_last_slash($str) {
  return substr($str,-1,1) == '/' ? substr($str,0,strlen($str)-1) : $str;
}