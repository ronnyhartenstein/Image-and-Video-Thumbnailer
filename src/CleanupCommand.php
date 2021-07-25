<?php

namespace RhFlow\Thumbnailer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CleanupCommand extends BaseCommand
{
    /** @var string  */
    protected $lockfile = '/tmp/mp4-thumbnailer.pid';

    protected function configure()
    {
        $this
            ->setName('thumbnail:cleanup')
            ->setDescription('Deletes files from target, which does not exists on source.')
            ->addArgument('source', InputArgument::REQUIRED, 'Path with source files')
            ->addArgument('target', InputArgument::REQUIRED, 'Target path for shrinked video (e.g. Nextcloud sync folder)')
            ->addOption('force', 'f', InputOption::VALUE_NONE)
            ->setHelp('MP4 Thumbnail Creator');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
    }

    /**
     * Check all files of target directory..
     */
    function shellcommandFindFiles(string $source_root, string $target_root)
    {
        return "find " . myescapeshellarg($target_root) . " -type f -iname \"*.jpg\" -or -iname \"*.mp4\"";
    }

    /**
     * .. if they exists in source..
     */
    protected function import(string $source_root, string $source_file, string $target_root, bool $force, bool $dry): bool
    {
        $target_file = $source_file;
        unset ($source_file);

        $this->log->debug("Check target file: $target_file");
        $target_file_wo_root = substr($target_file, strlen($target_root));
        $target_dir = dirname($target_file_wo_root);
        $this->log->debug("Target dir: $target_dir");

        $source_file = $source_root . preg_replace('/\.[a-zA-Z]+$/', '.mp4', $target_file_wo_root);
        $this->log->debug("Source file: $source_file");
        if (file_exists($source_file)) {
            $this->log->debug("Skip '$target_file_wo_root'. Source file exists.");
            return false;
        }

        $this->log->debug("Source file doesn't exist - remove '$target_file'.");
        if (!$dry) {
            unlink($target_file);
        }

        return true;
    }
}