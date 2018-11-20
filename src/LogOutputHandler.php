<?php

namespace RhFlow\Thumbnailer;

use \Symfony\Component\Console\Style\SymfonyStyle;
use \Monolog\Logger;

class LogOutputHandler extends \Monolog\Handler\AbstractProcessingHandler {

    public function __construct(SymfonyStyle $output, $level = Logger::DEBUG, $bubble = true)
    {
        $this->output = $output;
        parent::__construct($level, $bubble);
    }

    protected function write(array $record)
    {
        if ($record['level'] >= \Monolog\Logger::ERROR) {
            $this->output->error($record['message']);
        } else if ($record['level'] >= \Monolog\Logger::WARNING) {
            $this->output->warning($record['message']);
        } else if ($record['level'] >= \Monolog\Logger::INFO) {
            $this->output->text($record['message']);
        } else {
            $this->output->text($record['message']);
        }
    }
}