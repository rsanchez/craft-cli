<?php

namespace CraftCli\Command;

use Craft\ConsoleApp as Craft;

interface NeedsCraftInterface
{
    /**
     * Set Craft app instance
     * @param \Craft\ConsoleApp $craft
     */
    public function setCraft(Craft $craft);

    /**
     * Set Craft environment
     * @param string $environment
     */
    public function setEnvironment($environment);

    /**
     * Set Craft app path
     * @param string $path
     */
    public function setAppPath($path);

    /**
     * Set Craft base path
     * @param string $path
     */
    public function setBasePath($path);

    /**
     * Set Craft config path
     * @param string $path
     */
    public function setConfigPath($path);

    /**
     * Set Craft storage path
     * @param string $path
     */
    public function setStoragePath($path);

    /**
     * Set Craft plugins path
     * @param string $path
     */
    public function setPluginsPath($path);

    /**
     * Set Craft templates path
     * @param string $path
     */
    public function setTemplatesPath($path);

    /**
     * Set Craft translations path
     * @param string $path
     */
    public function setTranslationsPath($path);
}
