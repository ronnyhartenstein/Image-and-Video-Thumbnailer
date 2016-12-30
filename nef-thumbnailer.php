<?php

// run in background on Idle
proc_nice(20);


// Logging while testing
error_reporting(E_ALL);
$debug = true;

// Loggin in production..
// error_reporting(E_ALL & ~E_NOTICE);
// $debug = false;



$opt = getopt("s:t:");
// var_dump($opt);
if (empty($opt['s']) || empty($opt['t']) || isset($opt['h'])) {
  $script = basename(__FILE__);
  print <<<HELP
Call: php $script -s '/path/to/foto-archiv' -t '/path/to/nextcloud/foto-thumbs'
Options:
  -h            this help
  -s path/to    Path with source NEF files
  -t path/to    Target path for thumbnails (especially Nextcloud sync folder)
HELP;
  exit;
}

/*
 * Fetch parameters
 */

$source_root = strip_last_slash($opt['s']);
if (!file_exists($source_root)) {
  die("ERROR: source doesn't exists or is not a directory! $source_root");
}
$target_root = strip_last_slash($opt['t']);
if (!file_exists($target_root)) {
  die("ERROR: target doesn't exists or is not a directory! $target_root");
}

/*
 * Scan source
 */

// find /Users/ronny/Pictures/2016/*  -type f -iname "*.jpg" -or -iname "*.nef" > /tmp/thumbnailer_src.lst
log_debug("Glob: $source_root/*.{jpg,JPG,NEF}");
$source_files = array();
foreach (glob($source_root.'/*.{jpg,JPG,NEF}', GLOB_BRACE) as $source_file) {
  log_debug("Source file: $source_file");
  $source_file_wo_root = substr($source_file,strlen($source_root));
  $source_dir = dirname($source_file_wo_root);
  log_debug("Source dir: $source_dir");
  
  $target_file = $target_root.preg_replace('/\.[a-zA-Z]+$/','.jpg',$source_file_wo_root);
  log_debug("Target file: $target_file");
  if (file_exists($target_file)) {
    log_info("Target file exists. Skip.");
    continue;
  } 

  $target_dir = dirname($target_file);
  log_debug("Target dir: $target_dir");
  if (!file_exists($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
      log_error("Can't create target dir: $target_dir");
      continue;
    }
  }
  
  $tmp = explode('.',$source_file);
  $source_ext = strtolower(end($tmp));
  log_debug("Source Ext: $source_ext");
  if ($source_ext == 'nef') {
    $cmd = "dcraw -c -e $source_file | convert - -thumbnail 2048x2048 $target_file";
  } else {
    $cmd = "convert $source_file -thumbnail 2048x2048 $target_file";
  }
  log_debug("Run: $cmd");
  
  $output = array(); 
  $return_val = 0;
  exec($cmd, $output, $return_val);
  //var_dump($output);
  //var_dump($return_val);
  if ($return_val > 0) {
    log_warning("Run of '$cmd' returns $return_val\n");
  }
}



function log_error($msg) {
  // echo "ERROR: $msg\n";
  trigger_error($msg, E_USER_ERROR);
}

function log_warning($msg) {
  // echo "WARN: $msg\n";
  trigger_error($msg, E_USER_WARNING);
}

function log_debug($msg) {
  global $debug;
  if ($debug) {
    echo "DEBUG: $msg\n";
  }
//  trigger_error($msg, E_USER_NOTICE);
}

function log_info($msg) {
  if (ini_get('error_reporting') & E_NOTICE) {
    echo "INFO: $msg\n";
  }
//  trigger_error($msg, E_USER_NOTICE);
}

function strip_last_slash($str) {
  return substr($str,-1,1) == '/' ? substr($str,0,strlen($str)-1) : $str;
}