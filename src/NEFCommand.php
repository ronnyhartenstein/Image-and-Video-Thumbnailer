<?php

namespace RhFlow\Thumbnailer;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class NEFCommand extends BaseCommand
{
    /** @var string  */
    protected $lockfile = '/tmp/nef-thumbnailer.pid';

    protected function configure()
    {
        $this
            ->setName('thumbnail:nef')
            ->setDescription('Erstellt Thumbnails aus Rohdaten-Bildern.')
            ->addArgument('source', InputArgument::REQUIRED, 'Path with source NEF files')
            ->addArgument('target', InputArgument::REQUIRED, 'Target path for thumbnails (e.g. Nextcloud sync folder)')
            ->addOption('force', 'f', InputOption::VALUE_NONE)
            ->setHelp('NEF Thumbnail Creator');
    }

    function shellcommandFindSourceFiles(string $source_root)
    {
        return "find " . escapeshellarg($source_root) . " -type f -iname \"*.jpg\" -or -iname \"*.nef\"";
    }

    protected function import(string $source_root, string $source_file, string $target_root, bool $force): bool
    {
        $this->log->debug("Source file: $source_file");
        $source_file_wo_root = substr($source_file, strlen($source_root));
        $source_dir = dirname($source_file_wo_root);
        $this->log->debug("Source dir: $source_dir");

        $target_file = $target_root . preg_replace('/\.[a-zA-Z]+$/', '.jpg', $source_file_wo_root);
        $this->log->debug("Target file: $target_file");
        if (file_exists($target_file) && !isset($opt['f'])) {
            $this->log->debug("Skip '$source_file_wo_root'. Target file exists.");
            return false;
        }

        $target_dir = dirname($target_file);
        $this->log->debug("Target dir: $target_dir");
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                $this->log->error("Can't create target dir: $target_dir");
                return false;
            }
        }

        $tmp = explode('.', $source_file);
        $source_ext = strtolower(end($tmp));
        $this->log->debug("Source Ext: $source_ext");
        if ($source_ext == 'nef') {
            $cmd = "dcraw -c -e " . escapeshellarg($source_file) . " | convert - -strip -resize 2048x2048 -quality 85 " . escapeshellarg($target_file);
        } else {
            // http://www.imagemagick.org/Usage/thumbnails/
            $cmd = "convert " . escapeshellarg($source_file) . " -auto-orient -strip -resize 2048x2048 -quality 85 " . escapeshellarg($target_file);
        }
        $this->log->debug("Run: $cmd");
        if (!$this->run_message_showed) {
            $this->log->info("Creating new thumbnails..");
            $this->run_message_showed = true;
        }
        $output = [];
        $return_var = 0;
        exec($cmd, $output, $return_var);
        //var_dump($output);
        //var_dump($return_val);
        if ($return_var > 0) {
            $this->log->warning("Run of '$cmd' returns $return_var\n");
            return false;
        }

        return true;
    }
}