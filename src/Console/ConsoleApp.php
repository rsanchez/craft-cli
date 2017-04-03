<?php

namespace CraftCli\Console;

use Craft\ConsoleApp as CraftConsoleApp;

class ConsoleApp extends CraftConsoleApp
{
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
