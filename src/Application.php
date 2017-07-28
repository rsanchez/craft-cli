<?php

namespace CraftCli;

use CraftCli\Command\ExemptFromBootstrapInterface;
use CraftCli\Command\Command as BaseCommand;
use CraftCli\Command\NeedsCraftInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Dotenv\Dotenv;
use ReflectionClass;
use RuntimeException;

class Application extends ConsoleApplication
{
    /**
     * Symfony Console Application name
     */
    const NAME = 'Craft CLI';

    /**
     * Symfony Console Application version
     */
    const VERSION = '0.16.0';

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
     * Path to the craft folder
     * @var string
     */
    protected $craftPath = 'craft';

    /**
     * Path to the Craft app folder
     * @var string
     */
    protected $appPath = 'craft/app';

    /**
     * Path to the Composer vendor folder
     * @var string
     */
    protected $vendorPath;

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

    /**
     * Configuration items
     * @var array
     */
    protected $config = array();

    public function __construct($vendorPath = null)
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->vendorPath = $vendorPath;

        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(ConsoleEvents::COMMAND, array($this, 'onCommand'));

        $this->setDispatcher($dispatcher);

        $this->loadConfig();

        foreach ($this->findCommandsInDir(__DIR__.'/Command', '\\CraftCli\\Command') as $command) {
            $this->registerCommand($command);
        }
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

        $command->setApplication($this);

        $output = $event->getOutput();

        if (! $this->isCommandExemptFromBootstrap($command)) {
            if (! $this->canBeBootstrapped()) {
                throw new \Exception('Your craft path could not be found.');
            }

            $craft = $this->bootstrap();

            if ($command instanceof NeedsCraftInterface) {
                $command->setCraft($craft);
                $command->setEnvironment(CRAFT_ENVIRONMENT);
                $command->setAppPath(CRAFT_APP_PATH);
                $command->setBasePath(CRAFT_BASE_PATH);
                $command->setConfigPath(CRAFT_CONFIG_PATH);
                $command->setPluginsPath(CRAFT_PLUGINS_PATH);
                $command->setStoragePath(CRAFT_STORAGE_PATH);
                $command->setTemplatesPath(CRAFT_TEMPLATES_PATH);
                $command->setTranslationsPath(CRAFT_TRANSLATIONS_PATH);
            }
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
        return file_exists($this->appPath.'/Craft.php');
    }

    /**
     * Boot up craft
     * @param  bool $isInstalling
     * @return \Craft\ConsoleApp
     */
    public function bootstrap($isInstalling = false)
    {
        $craftPath = $this->craftPath;
        $appPath = $this->appPath;

        if ($this->vendorPath) {
            return require $this->vendorPath.'/craft-cli/bootstrap/src/bootstrap-craft2.php';
        }

        return require __DIR__.'/../../bootstrap/src/bootstrap-craft2.php';
    }

    /**
     * Get the environment from the --environment option
     * or from the CRAFT_ENVIRONMENT env variable
     * @return string|null
     */
    protected function getEnvironmentOption()
    {
        $definition = new InputDefinition();

        $definition->addOption(new InputOption('environment', null, InputOption::VALUE_REQUIRED));

        $input = new Console\GlobalArgvInput(null, $definition);

        return $input->getOption('environment') ?: getenv('CRAFT_ENVIRONMENT');
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

        $config = array_merge($config, $this->getComposerConfig());

        $this->config = $config;

        // Set the craft path
        if (isset($config['craft_path'])) {
            $this->craftPath = $config['craft_path'];
        }

        $dotenvPath = isset($config['dotenv_path']) ? $config['dotenv_path'] : getcwd();

        if (file_exists($dotenvPath.'/.env')) {
            $dotenv = new Dotenv($dotenvPath);
            $dotenv->load();
        }

        $environment = empty($config['environment']) ? $this->getEnvironmentOption() : $config['environment'];

        if ($environment) {
            define('CRAFT_ENVIRONMENT', $environment);
        }

        if (isset($config['craft_app_path'])) {
            $this->appPath = $config['craft_app_path'];
            define('CRAFT_APP_PATH', rtrim($config['craft_app_path'], '/').'/');
        }
        
        if (isset($config['craft_framework_path'])) {
            define('CRAFT_FRAMEWORK_PATH', rtrim($config['craft_framework_path'], '/').'/');
        }

        if (isset($config['craft_config_path'])) {
            define('CRAFT_CONFIG_PATH', rtrim($config['craft_config_path'], '/').'/');
        }

        if (isset($config['craft_plugins_path'])) {
            define('CRAFT_PLUGINS_PATH', rtrim($config['craft_plugins_path'], '/').'/');
        }

        if (isset($config['craft_storage_path'])) {
            define('CRAFT_STORAGE_PATH', rtrim($config['craft_storage_path'], '/').'/');
        }

        if (isset($config['craft_templates_path'])) {
            define('CRAFT_TEMPLATES_PATH', rtrim($config['craft_templates_path'], '/').'/');
        }

        if (isset($config['craft_translations_path'])) {
            define('CRAFT_TRANSLATIONS_PATH', rtrim($config['craft_translations_path'], '/').'/');
        }

        if (isset($config['craft_vendor_path'])) {
            define('CRAFT_VENDOR_PATH', rtrim($config['craft_vendor_path'], '/').'/');
        }
    }

    /**
     * Get craft-cli configuration stored in the root composer.json
     * @return array
     */
    public function getComposerConfig()
    {
        if (! $this->vendorPath) {
            return array();
        }

        $jsonPath = rtrim($this->vendorPath, '/').'/../composer.json';

        if (! file_exists($jsonPath)) {
            return array();
        }

        $jsonContents = file_get_contents($jsonPath);

        if (! $jsonContents) {
            return array();
        }

        $package = json_decode($jsonContents, true);

        if (! isset($package['extra']['craft-cli'])) {
            return array();
        }

        $config = $package['extra']['craft-cli'];

        if (isset($config['commandDirs'])) {
            foreach ($config['commandDirs'] as $namespace => $commandDir) {
                if (!$this->isPathAbsolute($commandDir)) {
                    $config['commandDirs'][$namespace] = rtrim($this->vendorPath, '/').'/../'.$commandDir;
                }
            }
        }

        return $config;
    }

    /**
     * Get all configuration items
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the specified config item
     * @param  string $key
     * @return mixed
     */
    public function getConfigItem($key)
    {
        return isset($this->config[$key]) ? $this->config[$key] : null;
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
     * Get the path to the craft folder
     * @param  string $craftPath
     * @return string
     */
    public function setCraftPath($craftPath)
    {
        return $this->craftPath = $craftPath;
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
     * Find any user-defined Commands in the config
     * and add them to the Application
     * @return void
     */
    public function addUserDefinedCommands()
    {
        $namespace = isset($this->config['namespace'])
            ? rtrim($this->config['namespace'], '\\').'\\'
            : null;

        if (! empty($this->config['commandDirs'])) {
            foreach ($this->config['commandDirs'] as $commandNamespace => $commandDir) {
                // handle deprecated indexed array
                if (is_numeric($commandNamespace)) {
                    $commandNamespace = $namespace;
                }

                if ($this->isPathAbsolute($commandDir)) {
                    $path = $commandDir;
                } else {
                    $path = getcwd().DIRECTORY_SEPARATOR.$commandDir;
                }

                if (is_dir($path)) {
                    $commands = $this->findCommandsInDir($path, $commandNamespace, true);

                    foreach ($commands as $command) {
                        $this->registerCommand($command);
                    }
                }
            }
        }

        if (! empty($this->config['commands'])) {
            foreach ($this->config['commands'] as $command) {
                $this->registerCommand($namespace.$command);
            }
        }
    }

    /**
     * Find any Command plugins installed via composer
     * and add them to the Application
     * @return void
     */
    public function addComposerPlugins()
    {
        if (! $this->vendorPath) {
            return;
        }

        $installedJsonPath = rtrim($this->vendorPath, '/').'/composer/installed.json';

        if (! file_exists($installedJsonPath)) {
            return;
        }

        $installedJsonContents = file_get_contents($installedJsonPath);

        if (! $installedJsonContents) {
            return;
        }

        $installed = json_decode($installedJsonContents, true);

        if (! $installed || ! is_array($installed)) {
            return;
        }

        $plugins = array_filter($installed, function ($package) {
            return ! empty($package['extra']['craft-cli']['commands']) || ! empty($package['extra']['craft-cli']['commandDirs']);
        });

        foreach ($plugins as $package) {
            $namespace = isset($package['extra']['craft-cli']['namespace'])
                ? rtrim($package['extra']['craft-cli']['namespace'], '\\').'\\'
                : null;

            if (! empty($package['extra']['craft-cli']['commandDirs'])) {
                foreach ($package['extra']['craft-cli']['commandDirs'] as $commandNamespace => $commandDir) {
                    // handle deprecated indexed array
                    if (is_numeric($commandNamespace)) {
                        $commandNamespace = $namespace;
                    }

                    $path = rtrim($this->vendorPath, '/').'/'.$package['name'].'/'.$commandDir;

                    $commands = $this->findCommandsInDir($path, $commandNamespace);

                    foreach ($commands as $command) {
                        $this->registerCommand($command);
                    }
                }
            }

            if (! empty($package['extra']['craft-cli']['commands'])) {
                foreach ($package['extra']['craft-cli']['commands'] as $command) {
                    $this->registerCommand($namespace.$command);
                }
            }
        }
    }

    /**
     * Get a list of Symfony Console Commands classes
     * in the specified directory
     *
     * @param  string $dir
     * @param  string $namespace
     * @param  bool   $autoload
     * @return array
     */
    public function findCommandsInDir($dir, $namespace = null, $autoload = false)
    {
        return $this->findClassInDir('Symfony\\Component\\Console\\Command\\Command', $dir, $namespace, $autoload);
    }

    /**
     * Get a list of classes
     * in the specified directory
     *
     * @param  string $subclassOf
     * @param  string $dir
     * @param  string $namespace
     * @param  bool   $autoload
     * @return array
     */
    public function findClassInDir($subclassOf, $dir, $namespace = null, $autoload = false)
    {
        $commands = array();

        if (!is_dir($dir)) {
            return $commands;
        }

        if ($namespace) {
            $namespace = rtrim($namespace, '\\').'\\';
        }

        $dir = rtrim($dir, '/');

        $files = scandir($dir);

        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if ($extension !== 'php') {
                continue;
            }

            $class = $namespace.basename($file, '.php');

            if (! class_exists($class)) {
                if ($autoload) {
                    require_once $dir.'/'.$file;
                } else {
                    continue;
                }
            }

            $reflectionClass = new ReflectionClass($class);

            if (! $reflectionClass->isInstantiable()) {
                continue;
            }

            if (! $reflectionClass->isSubclassOf($subclassOf)) {
                continue;
            }

            $commands[] = $class;
        }

        return $commands;
    }

    /**
     * Set the path to the Composer vendor folder
     * @param string $vendorPath
     */
    public function setVendorPath($vendorPath)
    {
        $this->vendorPath = $vendorPath;
    }

    /**
     * Add a command to the Application by class name
     * or callback that return a Command class
     * @param  string|callable $class class name or callback that returns a command
     * @return void
     */
    public function registerCommand($class)
    {
        // is it a callback or a string?
        if (is_callable($class)) {
            $this->add(call_user_func($class, $this));
        } else {
            $this->add(new $class());
        }
    }

    /**
     * Check if path is absolute (or relative)
     * @param  string  $path
     * @return boolean
     */
    public function isPathAbsolute($path)
    {
        // starts with dot
        if (strncmp($path, '.', 1) === 0) {
            return true;
        }

        $isWindows = strncmp(strtoupper(PHP_OS), 'WIN', 3) === 0;

        if ($isWindows) {
            // matches drive + path notation (C:\) or \\network-drive\
            return (bool) preg_match('/^([a-zA-Z]:\\\\|\\\\\\\\)/', $path);
        }

        // starts with slash
        return strncmp($path, '/', 1) === 0;
    }
}
