<?php

namespace RhFlow\Thumbnailer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MP4Command extends BaseCommand
{
    /** @var string  */
    protected $lockfile = '/tmp/mp4-thumbnailer.pid';

    protected function configure()
    {
        $this
            ->setName('thumbnail:mp4')
            ->setDescription('Erstellt Thumbnails aus Videos.')
            ->addArgument('source', InputArgument::REQUIRED, 'Path with source MP4/MOV files')
            ->addArgument('target', InputArgument::REQUIRED, 'Target path for shrinked video (e.g. Nextcloud sync folder)')
            ->addOption('force', 'f', InputOption::VALUE_NONE)
            ->setHelp('MP4 Thumbnail Creator');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
    }

    function shellcommandFindFiles(string $source_root, string $target_root)
    {
        return 'find ' . myescapeshellarg($source_root) . ' -type f \( -iname "*.mp4" -or -iname "*.mov" \) -not -iname "._*"';
    }

    protected function import(string $source_root, string $source_file, string $target_root, bool $force, bool $dry): bool
    {
        $this->log->debug("Source file: $source_file");
        $source_file_wo_root = substr($source_file, strlen($source_root));
        $source_dir = dirname($source_file_wo_root);
        $this->log->debug("Source dir: $source_dir");

        $target_file = $target_root . preg_replace('/\.[a-zA-Z]+$/', '.mp4', $source_file_wo_root);
        $this->log->debug("Target file: $target_file");
        if (file_exists($target_file)) {
            if ($force) {
                $this->log->debug("Remove '$target_file'.");
                unlink($target_file);
            } else {
                $this->log->debug("Skip '$source_file_wo_root'. Target file exists.");
                return false;
            }
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

        $cmd = "ffprobe -v error -show_entries stream"
            . " -of default=noprint_wrappers=1"
            . " " . myescapeshellarg($source_file);
        $this->log->debug($cmd);
        $infos = [];
        $output = [];
        $return_var = 0;
        $width = 0;
        $height = 0;
        exec($cmd, $output, $return_var);
        foreach ($output as $v) {
            if (strpos($v, '=') !== false) {
                list($kk, $vv) = explode('=', $v);
                //$this->log->debug($kk.' = '.$vv);
                $infos[$kk] = $vv; // $width & $height :)
            }
        }
        if (!empty($infos['rotation']) && ($infos['rotation'] == '-90' || $infos['rotation'] == '90')) {
            $width = $infos['height'];
            $height = $infos['width'];
            $this->log->debug("Hochkant Format erkannt");
            $hochkant = true;
        } else {
            $width = $infos['width'];
            $height = $infos['height'];
            $hochkant = false;
        }
        $this->log->debug("Source video format: ${width}x${height}");

        if ($source_ext !== 'mp4' && $source_ext !== 'mov') {
            $this->log->info("Unsupported format: " . $source_ext);
            return false;
        }

        $target_video_bitrate = '1250k'; // 800k is recommended for DVD (PAL-wide, 1024x576)
        if (!empty($width) && !empty($height)) {
            $ratio = $width / $height;
        } else {
            $ratio = 1280 / 720; // standard ratio today
        }
        $this->log->debug("Ratio: ${ratio}");
        if ($hochkant) {
            $target_video_size = '720x' . intval(720 / $ratio); // HD ready!    
            if ($height < 720) {
                $target_video_size = '850x'.intval($ratio * 850); // even smaller.
                $target_video_bitrate = '800k';
            }
        } else {
            $target_video_size = intval($ratio * 720) . 'x720'; // HD ready!
            if ($width < 720) {
                $target_video_size = intval($ratio * 480) . 'x480'; // even smaller.
                $target_video_bitrate = '400k';
            }
        }
//        $target_audio_bitrate = '96k'; // lowest br with acceptable sound
        $cmd = "ffmpeg -y"
            . " -loglevel error"
            . " -i " . myescapeshellarg($source_file)
            . " -c:v libx264"
            . " -b:v $target_video_bitrate"
            . " -s $target_video_size"
            . " -pix_fmt yuv420p"
            // ." -c:a libmp3lame"
            // ." -b:a $target_audio_bitrate"
            . " " . myescapeshellarg($target_file);

        $this->log->info("Process $source_file_wo_root (${width}x${height} -> $target_video_size) ..");
        $this->log->debug("Run: $cmd");
        if (!$this->run_message_showed) {
            $this->log->info("New videos detected. Create thumbnails..");
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