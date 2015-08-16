<?php

namespace CraftCli\Support\Downloader;

class Downloader extends BaseDownloader
{
    /**
     * Constructor
     * @param string $url
     * @param string $path
     */
    public function __construct($url, $path)
    {
        $this->url = $url;

        $this->path = $path;
    }
}
