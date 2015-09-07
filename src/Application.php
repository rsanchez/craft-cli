<?php

namespace CraftCli;

use CraftCli\Command\ExemptFromBootstrapInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;

class Application extends ConsoleApplication
{
    /**
     * Symfony Console Application name
     */
    const NAME = 'Craft CLI';

    /**
     * Symfony Console Application version
     */
    const VERSION = '0.0.0-alpha';

    /**
     * Default configuration file name
     */
    const FILENAME = '/.craft-cli.php';

    /**
     * Whether the craft folder has been set/guessed correctly
     * @var bool
     */
    protected $hasValidCraftPath = false;

    /**
     * A list of Command objects
     * @var array
     */
    protected $userDefinedCommands = array();

    /**
     * A list of callbacks to fire on events
     * @var array
     */
    protected $eventCallbacks = array();

    /**
     * Path to the craft folder
     * @var string
     */
    protected $craftPath = 'craft';

    /**
     * Author name for generated addons
     * @var string
     */
    protected $addonAuthorName = '';

    /**
     * Author url for generated addons
     * @var string
     */
    protected $addonAuthorUrl = '';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(ConsoleEvents::COMMAND, array($this, 'onCommand'));

        $this->setDispatcher($dispatcher);

        $this->loadConfig();

        $this->add(new Command\InitCommand());
        $this->add(new Command\ConsoleCommand());
        $this->add(new Command\ShowConfigCommand());
        $this->add(new Command\GenerateCommandCommand());
        $this->add(new Command\DbBackupCommand());
        $this->add(new Command\InstallCraftCommand());
        $this->add(new Command\InstallPluginCommand());
        $this->add(new Command\ClearCacheCommand());
    }

    /**
     * On Command Event Handler
     *
     * Check if the current command requires EE bootstrapping
     * and throw an exception if EE is not bootstrapped
     *
     * @param  ConsoleCommandEvent $event
     * @return void
     */
    public function onCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();

        $output = $event->getOutput();

        if (! $this->isCommandExemptFromBootstrap($command)) {
            if (! $this->canBeBootstrapped()) {
                throw new \Exception('Your craft path could not be found.');
            }

            $this->bootstrap();
        }
    }

    /**
     * Check whether a command should be exempt from bootstrapping
     * @param  \Symfony\Component\Console\Command\Command $command
     * @return boolean
     */
    protected function isCommandExemptFromBootstrap(SymfonyCommand $command)
    {
        $commandName = $command->getName();

        if ($commandName === 'help' || $commandName === 'list') {
            return true;
        }

        return $command instanceof ExemptFromBootstrapInterface;
    }

    /**
     * Whether or not a valid system folder was found
     * @return bool
     */
    public function canBeBootstrapped()
    {
        return file_exists($this->craftPath.'/app/Craft.php');
    }

    /**
     * Boot up craft
     * @return void
     */
    public function bootstrap()
    {
        $this->fire('bootstrap.before');

        $craftPath = $this->craftPath;

        require __DIR__.'/bootstrap-craft2.php';

        $this->fire('bootstrap.after');
    }

    /**
     * Get the environment from ENV PHP constant,
     * defined in config.php, or an environment
     * variable called ENV
     * @return string|null
     */
    public function getEnvironment()
    {
        if (defined('ENV')) {
            return ENV;
        } elseif (getenv('ENV')) {
            return getenv('ENV');
        }

        return null;
    }

    /**
     * Traverse up a directory to find a config file
     *
     * @param  string|null $dir defaults to getcwd if null
     * @return string|null
     */
    protected function findConfigFile($dir = null)
    {
        if (is_null($dir)) {
            $dir = getcwd();
        }

        if ($dir === '/') {
            return null;
        }

        if (file_exists($dir.'/'.self::FILENAME)) {
            return $dir.'/'.self::FILENAME;
        }

        $parentDir = dirname($dir);

        if ($parentDir && is_dir($parentDir)) {
            return $this->findConfigFile($parentDir);
        }

        return null;
    }

    /**
     * Looks for ~/.craft-cli.php and ./.craft-cli.php
     * and combines them into an array
     *
     * @return void
     */
    protected function loadConfig()
    {
        // Load configuration file(s)
        $config = array();

        // Look for ~/.craft-cli.php in the user's home directory
        if (isset($_SERVER['HOME']) && file_exists($_SERVER['HOME'].self::FILENAME)) {
            $temp = require $_SERVER['HOME'].self::FILENAME;

            if (is_array($temp)) {
                $config = array_merge($config, $temp);
            }

            unset($temp);
        }

        $configFile = $this->findConfigFile();

        // Look for the config file in the current working directory
        if ($configFile) {
            $temp = require $configFile;

            if (is_array($temp)) {
                $config = array_merge($config, $temp);
            }

            unset($temp);
        }

        // Spoof $_SERVER variables
        if (isset($config['server']) && is_array($config['server'])) {
            $_SERVER = array_merge($_SERVER, $config['server']);
        }

        // Set the craft path
        if (isset($config['craft_path'])) {
            $this->craftPath = $config['craft_path'];
        }

        // Session class needs this
        if (! isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'localhost';
        }

        // Add user-defined commands from config
        if (isset($config['commands']) && is_array($config['commands'])) {
            $this->userDefinedCommands = $config['commands'];
        }

        // Add event callbacks from the config
        if (isset($config['callbacks']) && is_array($config['callbacks'])) {
            $this->eventCallbacks = $config['callbacks'];
        }

        if (isset($config['addon_author_name'])) {
            $this->addonAuthorName = $config['addon_author_name'];
        }

        if (isset($config['addon_author_url'])) {
            $this->addonAuthorUrl = $config['addon_author_url'];
        }
    }

    /**
     * Get the path to the craft folder
     * @return string
     */
    public function getCraftPath()
    {
        return $this->craftPath;
    }

    /**
     * Get the name of the craft folder
     * @return string
     */
    public function getCraftFolder()
    {
        return basename($this->craftPath);
    }

    /**
     * Get the default addon author name
     * @return string
     */
    public function getAddonAuthorName()
    {
        return $this->addonAuthorName;
    }

    /**
     * Get the default addon author URL
     * @return string
     */
    public function getAddonAuthorUrl()
    {
        return $this->addonAuthorUrl;
    }

    /**
     * Find any commands defined in addons
     * and add them to the Application
     */
    public function addThirdPartyCommands()
    {
        if (! $this->canBeBootstrapped()) {
            return;
        }

        if (! ee()->extensions->active_hook('CraftCli_add_commands')) {
            return;
        }

        $commands = array();

        $commands = ee()->extensions->call('CraftCli_add_commands', $commands, $this);

        if (is_array($commands)) {
            foreach ($commands as $command) {
                if ($command instanceof SymfonyCommand) {
                    $this->add($command);
                }
            }
        }
    }

    /**
     * Find any user-defined Commands in the config
     * and add them to the Application
     * @return void
     */
    public function addUserDefinedCommands()
    {
        foreach ($this->userDefinedCommands as $classname) {
            // is it a callback or a string?
            if (is_callable($classname)) {
                $this->add(call_user_func($classname, $this));
            } else {
                $this->add(new $classname());
            }
        }
    }

    /**
     * Fire an event callback
     * @param  string $event
     * @return void
     */
    public function fire($event)
    {
        if (isset($this->eventCallbacks[$event]) && is_callable($this->eventCallbacks[$event])) {
            call_user_func($this->eventCallbacks[$event], $this);
        }
    }
}
