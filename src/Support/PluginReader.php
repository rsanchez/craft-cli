<?php

namespace CraftCli\Support;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class PluginReader
{
    /**
     * Directory in which to search for plugin file
     * @var string
     */
    protected $directory;

    /**
     * Path to the folder containing the plugin file
     * @var string
     */
    protected $path;

    /**
     * The name of the folder for this plugin
     * @var string
     */
    protected $folderName;

    /**
     * Constructor
     * @param string $directory
     */
    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Locate a <PluginName>Plugin.php file in the specified directory
     * @return bool
     */
    public function read()
    {
        $finder = new Finder();

        $finder->files()
            ->name('*Plugin.php')
            ->ignoreUnreadableDirs()
            ->exclude('vendor')
            ->in($this->directory);

        foreach ($finder as $file) {
            $this->folderName = strtolower($file->getBasename('Plugin.php'));

            $this->path = $file->getPath();

            return true;
        }

        return false;
    }

    /**
     * Get the folder name
     * @return string
     */
    public function getFolderName()
    {
        return $this->folderName;
    }

    /**
     * Get the plugin path
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
