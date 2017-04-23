<?php

namespace CraftCli\Bootstrap;

use Craft\ConsoleApp as CraftConsoleApp;
use CApplication;
use Exception;

class ConsoleApp extends CraftConsoleApp
{
    /**
     * @var \CraftCli\Bootstrap\ConsoleApp
     */
    protected static $instance;

    /**
     * @var boolean
     */
    protected $isInstalling = false;

    /**
     * @var boolean
     */
    protected $setIsNotInstallingAfterNextCheck = false;

    /**
     * @var boolean
     */
    protected $checkedIsInstalling = false;

    public function __construct($config = null, $isInstalling = false)
    {
        $this->isInstalling = $isInstalling;

        parent::__construct($config);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            throw new Exception('You must first set the instance via setInstance().');
        }
    }

    public static function setInstance(CApplication $app)
    {
        self::$instance = $app;
    }

    public function isConsole()
    {
        if ($this->isInstalling()) {
            return false;
        }

        return true;
    }

    /**
     * Set isInstalling
     * @param boolean $isInstalling
     */
    public function setIsInstalling($isInstalling)
    {
        $this->isInstalling = $isInstalling;
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalling()
    {
        if ($this->setIsNotInstallingAfterNextCheck) {
            if ($this->checkedIsInstalling) {
                $this->isInstalling = false;
                $this->setIsNotInstallingAfterNextCheck = false;
            } else {
                $this->checkedIsInstalling = true;
            }
        }

        return $this->isInstalling;
    }

    /**
     * Turn isInstalling
     * @return void
     */
    public function setIsNotInstallingAfterNextCheck()
    {
        $this->setIsNotInstallingAfterNextCheck = true;
    }
}
