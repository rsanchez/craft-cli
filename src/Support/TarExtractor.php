<?php

namespace CraftCli\Support;

use PharData;
use InvalidArgumentException;

class TarExtractor
{
    /**
     * Path to the .tar or .tar.gz
     * @var string
     */
    protected $filePath;

    /**
     * Path to which files are extracted
     * @var string
     */
    protected $extractionPath;

    /**
     * Whether to overwrite existing files (if necessary)
     * @var bool
     */
    protected $overwrite;

    /**
     * Constructor
     *
     * @param string $filePath
     * @param string $extractionPath
     * @param bool   $overwrite
     */
    public function __construct($filePath, $extractionPath, $overwrite = true)
    {
        if (! preg_match('/\.tar(\.gz)?$/', $filePath, $match)) {
            throw new InvalidArgumentException('$filePath must have a .tar or .tar.gz extension.');
        }

        if (! file_exists($filePath)) {
            throw new InvalidArgumentException('$filePath does not exist.');
        }

        if (! is_readable($filePath)) {
            throw new InvalidArgumentException('$filePath is not readable.');
        }

        if (! file_exists($extractionPath)) {
            throw new InvalidArgumentException('$extractionPath does not exist.');
        }

        if (! is_dir($extractionPath)) {
            throw new InvalidArgumentException('$extractionPath must be a directory.');
        }

        if (! is_writable($extractionPath)) {
            throw new InvalidArgumentException('$extractionPath must be writable.');
        }

        $this->filePath = $filePath;

        $this->extractionPath = $extractionPath;

        $this->overwrite = $overwrite;
    }

    /**
     * Extract the archive to the specified path
     * @return void
     */
    public function extract()
    {
        $tarPath = $this->filePath;

        // is it gzipped?
        if (preg_match('/^(.*?\.tar)\.gz$/', $this->filePath, $match)) {
            $pharData = new PharData($this->filePath);

            // creates a tar file in the same dir
            $pharData->decompress();

            // set the tar path (file without the .gz extension)
            $tarPath = $match[1];

            unset($pharData);

            // destroy the gzip file
            unlink($this->filePath);
        }

        // extract the tar
        $pharData = new PharData($tarPath);

        $pharData->extractTo($this->extractionPath, null, $this->overwrite);

        unset($pharData);

        // destroy the tar file
        unlink($tarPath);
    }
}
