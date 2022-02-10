<?php

namespace RhFlow\Thumbnailer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->addArgument('source', InputArgument::REQUIRED, 'Path with source NEF/CR2 files')
            ->addArgument('target', InputArgument::REQUIRED, 'Target path for thumbnails (e.g. Nextcloud sync folder)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite')
            ->addOption('dry', 'd', InputOption::VALUE_NONE, 'Dry run')
            ->setHelp('NEF Thumbnail Creator');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
    }

    function shellcommandFindFiles(string $source_root, string $target_root)
    {
        return 'find ' . myescapeshellarg($source_root) . ' -type f \( -iname "*.jpg" -or -iname "*.jpeg" -or -iname "*.nef" -or -iname "*.cr2" -or -iname "*.heic" \) -not -iname "._*"';
    }

    protected function import(string $source_root, string $source_file, string $target_root, bool $force, bool $force_hochkant, bool $dry): bool
    {
        $this->log->debug("Source file: $source_file");
        $source_file_wo_root = substr($source_file, strlen($source_root));
        $source_dir = dirname($source_file_wo_root);
        $this->log->debug("Source dir: $source_dir");

        $target_file = $target_root . preg_replace('/\.[a-zA-Z0-9]+$/', '.jpg', $source_file_wo_root);
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
        if ($source_ext == 'nef' || $source_ext == 'cr2') {
            $cmd = "dcraw -c -e " . myescapeshellarg($source_file) . " | convert - -strip -resize 2048x2048 -quality 85 " . myescapeshellarg($target_file);
        } else {
            // http://www.imagemagick.org/Usage/thumbnails/
            $cmd = "convert " . myescapeshellarg($source_file) . " -auto-orient -strip -resize 2048x2048 -quality 85 " . myescapeshellarg($target_file);
        }
        $this->log->debug("Run: $cmd");
        if (!$this->run_message_showed) {
            $this->log->info("Creating new thumbnails..");
            $this->run_message_showed = true;
        }
        $output = [];
        $return_var = 0;
        if (!$dry) {
            exec($cmd, $output, $return_var);
        }
        //var_dump($output);
        //var_dump($return_val);
        if ($return_var > 0) {
            $this->log->warning("Run of '$cmd' returns $return_var\n");
            return false;
        }

        return true;
    }
}