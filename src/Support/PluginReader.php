<?php

namespace CraftCli\Support;

use CFileHelper;
use SplFileInfo;
use Exception;

class PluginReader
{
    /**
     * Directory in which to search for plugin file
     * @var string
     */
    protected $directory;

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
     * @return SplFileInfo
     */
    public function read()
    {
        $files = CFileHelper::findFiles($this->directory, array(
            'fileTypes' => array('php'),
            'exclude' => array('/vendor', '/tests'),
        ));

        foreach ($files as $file) {
            if (preg_match('/Plugin$/', basename($file, '.php'))) {
                return new SplFileInfo($file);
            }
        }

        throw new Exception('Could not find a valid Craft plugin file.');
    }
}
