<?php

function getLogLevel()
{
    return \Monolog\Logger::DEBUG;
}

$app_log_handler = new \Monolog\Handler\StreamHandler('app.log', getLogLevel());
$log = new \Monolog\Logger('app');
$log->pushHandler($app_log_handler);
//$log->pushHandler(new \Monolog\Handler\ErrorLogHandler(
//    \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM,
//    $level
//));

set_exception_handler(function(Throwable $e) use ($log) {
    error_log(sprintf(
        "PHP-Fehler: %s at %s line %d",
        $e->getMessage(),
        $e->getFile(), $e->getLine()
    ), E_USER_ERROR);
    $log->error(sprintf(
        "%s at %s line %d\nTrace: %s",
        $e->getMessage(),
        $e->getFile(), $e->getLine(),
        $e->getTraceAsString()
    ));
});

$log_mp4 = new \Monolog\Logger('mp4');
$log_mp4->pushHandler($app_log_handler);

$log_nef = new \Monolog\Logger('nef');
$log_nef->pushHandler($app_log_handler);

$log_cleanup = new \Monolog\Logger('cleanup');
$log_cleanup->pushHandler($app_log_handler);

return [
    'mp4' => $log_mp4,
    'nef' => $log_nef,
    'cleanup' => $log_cleanup
];