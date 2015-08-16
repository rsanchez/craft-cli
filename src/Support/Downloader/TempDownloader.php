<?php

namespace CraftCli\Support\Downloader;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * Download file to the system temp folder
 */
class TempDownloader extends BaseDownloader
{
    /**
     * Constructor
     * @param string $url
     * @param string $extension
     */
    public function __construct($url, $extension = null)
    {
        $this->url = $url;

        $tempPath = tempnam(sys_get_temp_dir(), 'craft_cli_');

        if ($extension) {
            $this->path = $tempPath.'.'.ltrim($extension, '.');

            rename($tempPath, $this->path);
        } else {
            $this->path = $tempPath;
        }
    }
}
