<?php

namespace RhFlow\Thumbnailer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use \RuntimeException as Exception;

abstract class BaseCommand extends Command
{
    /** @var \Monolog\Logger */
    protected $log;

    /** @var bool */
    protected $run_message_showed = false;

    public function __construct(\Monolog\Logger $log)
    {
        $this->log = $log;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateLogToOutput($input, $output);

        // run in background on Idle
        proc_nice(20);

        /*
         * Fetch parameters
         */
        $source_root = strip_last_slash($input->getArgument('source'));
        if (!file_exists($source_root)) {
            throw new Exception("ERROR: source doesn't exists or is not a directory! $source_root");
        }
        $target_root = strip_last_slash($input->getArgument('target'));
        if (!file_exists($target_root)) {
            throw new Exception("ERROR: target doesn't exists or is not a directory! $target_root");
        }
        $force = $input->hasOption('force') ? $input->getOption('force') : false;
        if ($force) {
            $this->log->info("Option 'Force overwrite' given");
        }
        $dry = $input->hasOption('dry') ? $input->getOption('dry') : false;
        if ($dry) {
            $this->log->info("Option 'Dry run' given");
        }

        /*
         * Locking by PID-file, single process
         */
        $this->checkLocking();

        /*
         * Scan source
         */
        $successfull = 0;
        $this->run_message_showed = false;

        // find /Users/ronny/Pictures/2016/*  -type f -iname "*.jpg" -or -iname "*.nef" > /tmp/thumbnailer_src.lst
        // find /Users/ronny/Movies/2016/*  -type f -iname "*.mp4" > /tmp/thumbnailer_src.lst
        $source_files = array();
        $cmd = $this->shellcommandFindFiles($source_root, $target_root);
        exec($cmd, $source_files);
        $this->log->debug(count($source_files) . " source files found!");

        foreach ($source_files as $source_file) {
            if ($this->import($source_root, $source_file, $target_root, $force, $dry)) {
                $successfull++;
            }
        }
        if ($successfull > 0) {
            $this->log->info("DONE! Sucessfully converted $successfull files.");
        }

        $this->unlock();
    }

    protected function checkLocking()
    {
        if (file_exists($this->lockfile)) {
            $otherpid = intval(file_get_contents($this->lockfile));
            if ($otherpid > 0) {
                $output = [];
                $return_var = '';
                exec('ps -x |grep ' . $otherpid . ' | grep -v grep', $output, $return_var);
                if ($return_var == 0 && count($output) == 1) {
                    throw Exception('Other process is still running (' . $otherpid . ')');
                }
            } else {
                throw Exception('Lockfile don\'t contain a valid process id. Please check ' . $this->lockfile);
            }
        }
        file_put_contents($this->lockfile, getmypid());
    }
    protected function unlock()
    {
        unlink($this->lockfile);
    }

    protected function activateLogToOutput(InputInterface $input, OutputInterface $output)
    {
        $this->log->pushHandler(new LogOutputHandler(new SymfonyStyle($input, $output), getLogLevel()));
    }

    abstract function shellcommandFindFiles(string $source_root, string $target_root);

    abstract protected function import(string $source_root, string $source_file, string $target_root, bool $force, bool $dry): bool;
}