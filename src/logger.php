<?php

function getLogLevel()
{
    return \Monolog\Logger::DEBUG;
}

$log = new \Monolog\Logger('app');
$log->pushHandler(new \Monolog\Handler\StreamHandler('app.log', getLogLevel()));
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

return $log;