<?php

// run in background on Idle
proc_nice(20);

// Logging
error_reporting(E_ALL);
$debug = false;

// Resolve options
$opt = getopt("s:t:");
// var_dump($opt);
if (empty($opt['s']) || empty($opt['t']) || isset($opt['h'])) {
  $script = basename(__FILE__);
  print <<<HELP
Call: php $script -s '/path/to/foto-archive' -t '/path/to/nextcloud/foto-thumbs'
Options:
  -h            this help
  -s path/to    Path with source NEF files
  -t path/to    Target path for thumbnails (e.g. Nextcloud sync folder)
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
 * Locking by PID-file, single process
 */
$lockfile = '/tmp/nef-thumbnailer.pid';
if (file_exists($lockfile)) {
  $otherpid = intval(file_get_contents($lockfile));
  if ($otherpid > 0) {
    $output = []; $return_var = '';
    exec('ps -x |grep '.$otherpid.' | grep -v grep', $output, $return_var);
    if ($return_var == 0 && count($output) == 1) {
      log_warning('Other process is still running ('.$otherpid.')');
      exit;
    }
  } else {
    log_error('Lockfile don\'t contain a valid process id. Please check '.$lockfile);
    exit;
  }
}
file_put_contents($lockfile, getmypid());

/*
 * Scan source
 */
$successfull = 0;
$run_message_showed = false;

// find /Users/ronny/Pictures/2016/*  -type f -iname "*.jpg" -or -iname "*.nef" > /tmp/thumbnailer_src.lst
$source_files = array();
exec("find $source_root -type f -iname \"*.jpg\" -or -iname \"*.nef\"", $source_files); 
log_debug(count($source_files)." source files found!");

foreach ($source_files as $source_file) {
  log_debug("Source file: $source_file");
  $source_file_wo_root = substr($source_file,strlen($source_root));
  $source_dir = dirname($source_file_wo_root);
  log_debug("Source dir: $source_dir");
  
  $target_file = $target_root.preg_replace('/\.[a-zA-Z]+$/','.jpg',$source_file_wo_root);
  log_debug("Target file: $target_file");
  if (file_exists($target_file)) {
    log_debug("Skip '$source_file_wo_root'. Target file exists.");
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
    $cmd = "dcraw -c -e ".escapeshellarg($source_file)." | convert - -thumbnail 2048x2048 ".escapeshellarg($target_file);
  } else {
    $cmd = "convert ".escapeshellarg($source_file)." -thumbnail 2048x2048 ".escapeshellarg($target_file);
  }
  log_debug("Run: $cmd");
  if (!$run_message_showed) {
    log_info("Creating new thumbnails..");
    $run_message_showed = true;
  }
  $output = []; 
  $return_var = 0;
  exec($cmd, $output, $return_var);
  //var_dump($output);
  //var_dump($return_val);
  if ($return_val > 0) {
    log_warning("Run of '$cmd' returns $return_val\n");
  }
  $successfull++;
}
if ($successfull > 0) {
  log_info("DONE! Sucessfully converted $successfull images.");
}

unlink($lockfile);
exit;

/*
 * Helper
 */

function notify($message, $subtitle='') {
  global $argv;
  $cmd = dirname($argv[0]).'/Grunt-notify.app/Contents/MacOS/Grunt -message "'.$message.'"'
    . ' -title "NEF-Thumbnailer" -subtitle "'.$subtitle.'"'
    . ' -group "NEF-thumbnailer"';
  exec($cmd);
}

function log_error($msg) {
  // echo "ERROR: $msg\n";
  notify($msg, "FEHLER");
  trigger_error($msg, E_USER_ERROR);
}

function log_warning($msg) {
  // echo "WARN: $msg\n";
  notify($msg, "WARNUNG");
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
    notify($msg);
    echo "INFO: $msg\n";
  }
//  trigger_error($msg, E_USER_NOTICE);
}

function strip_last_slash($str) {
  return substr($str,-1,1) == '/' ? substr($str,0,strlen($str)-1) : $str;
}