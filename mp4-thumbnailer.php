<?php
include '_helper.php';

// run in background on Idle
proc_nice(20);

// Logging
error_reporting(E_ALL);
$debug = false;

// Resolve options
$opt = getopt("s:t:f");
// var_dump($opt); exit;
if (empty($opt['s']) || empty($opt['t']) || isset($opt['h'])) {
  $script = basename(__FILE__);
  print <<<HELP
Call: php $script -s '/path/to/movies' -t '/path/to/nextcloud/movie-thumbs'
Options:
  -h            this help
  -s path/to    Path with source video files (MP4)
  -t path/to    Target path for shrinked video (e.g. Nextcloud sync folder)
  -f            Force rebuild
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
$force = isset($opt['f']);

/*
 * Locking by PID-file, single process
 */
$lockfile = '/tmp/mp4-thumbnailer.pid';
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

// find /Users/ronny/Movies/2016/*  -type f -iname "*.mp4" > /tmp/thumbnailer_src.lst
$source_files = array();
exec("find $source_root -type f -iname \"*.mp4\"", $source_files); 
log_debug(count($source_files)." source files found!");

foreach ($source_files as $source_file) {
  log_debug("Source file: $source_file");
  $source_file_wo_root = substr($source_file,strlen($source_root));
  $source_dir = dirname($source_file_wo_root);
  log_debug("Source dir: $source_dir");
  
  $target_file = $target_root.preg_replace('/\.[a-zA-Z]+$/','.mp4',$source_file_wo_root);
  log_debug("Target file: $target_file");
  if (file_exists($target_file)) {
    if ($force) {
      log_debug("Remove '$target_file'.");
      unlink($target_file);
    } else {
      log_debug("Skip '$source_file_wo_root'. Target file exists.");
      continue;
    }
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

  $cmd = "ffprobe -v error -show_entries stream=width,height"
      ." -of default=noprint_wrappers=1"
      ." ".escapeshellarg($source_file);
  $output = []; 
  $return_var = 0;
  $width = 0; $height = 0;
  exec($cmd, $output, $return_var);
  foreach($output as $v) {
    list($kk,$vv) = explode('=', $v);
    $$kk = $vv; // $width & $height :)
  }
  log_debug("Source video format: ${width}x${height}");
  
  $target_video_size = '';
  if ($source_ext == 'mp4') {
    $target_video_bitrate = '1250k'; // 800k is recommended for DVD (PAL-wide, 1024x576)
    $ratio = $width / $height;
    $target_video_size = intval($ratio * 720) . 'x720'; // HD ready!
    if ($width < 720) {
      $target_video_size = intval($ratio * 480) . 'x480'; // even smaller.
      $target_video_bitrate = '800k';
    }
    $target_audio_bitrate = '96k'; // lowest br with acceptable sound
    $cmd = "ffmpeg -y"
          ." -loglevel error"
          ." -i ".escapeshellarg($source_file)
          ." -c:v libx264"
          ." -b:v $target_video_bitrate"
          ." -s $target_video_size"
          ." -pix_fmt yuv420p"
          // ." -c:a libmp3lame"
          // ." -b:a $target_audio_bitrate"
          ." ".escapeshellarg($target_file);
  } else {
    log_info("Unsupported format: ".$source_ext);
    continue;
  }
  log_info("Process $source_file_wo_root (${width}x${height} -> $target_video_size) ..");
  log_debug("Run: $cmd");
  if (!$run_message_showed) {
    log_info("New videos detected. Create thumbnails..");
    $run_message_showed = true;
  }
  $output = []; 
  $return_var = 0;
  exec($cmd, $output, $return_var);
  //var_dump($output);
  //var_dump($return_val);
  if ($return_var > 0) {
    log_warning("Run of '$cmd' returns $return_var\n");
  }
  $successfull++;
  // break; // debug one
}
if ($successfull > 0) {
  log_info("DONE! Sucessfully converted $successfull videos.");
}

unlink($lockfile);
exit;
