<?php

namespace CraftCli\Command;

use CraftCli\Handlebars\Loader\FilesystemLoader;
use CraftCli\Support\Downloader\TempDownloader;
use CraftCli\Support\TarExtractor;
use Exception;
use Handlebars\Handlebars;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCraftCommand extends DownloadCraftCommand
{
    /**
    * {@inheritdoc}
    */
    protected $name = 'install';

    /**
    * {@inheritdoc}
    */
    protected $description = 'Install Craft to the current directory.';

    /**
    * {@inheritdoc}
    */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), array(
            array(
                'site-name', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'Site Name.', // description
                'Craft CMS', // default value
            ),
            array(
                'site-url', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'Site Url.', // description
                '127.0.0.1', // default value
            ),
            array(
                'admin-user', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'Admin username.', // description
                'admin', // default value
            ),
            array(
                'admin-password', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'Admin password.', // description
                'password', // default value
            ),
            array(
                'admin-email', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'Admin email.', // description
                'admin@example.com', // default value
            ),
            array(
                'locale', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'Default locale.', // description
                'en', // default value
            ),
            array(
                'db-host', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'Database host.', // description
                'localhost', // default value
            ),
            array(
                'db-port', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'Database port.', // description
                3306, // default value
            ),
            array(
                'db-name', // name
                null, // shortcut
                InputOption::VALUE_REQUIRED, // mode
                'Database name.', // description
                null, // default value
            ),
            array(
                'db-user', // name
                null, // shortcut
                InputOption::VALUE_REQUIRED, // mode
                'MySQL username.', // description
                null, // default value
            ),
            array(
                'db-password', // name
                null, // shortcut
                InputOption::VALUE_REQUIRED, // mode
                'MySQL password.', // description
                null, // default value
            ),
            array(
                'db-admin-user', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'MySQL administrative user.', // description
                null, // default value
            ),
            array(
                'db-admin-password', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'MySQL administrative password.', //description
                null, // default value
            ),
            array(
                'create-db', // name
                null, // shortcut
                InputOption::VALUE_NONE, // mode
                'Create new database.', //description
            ),
        ));
    }

    protected function fire()
    {
        parent::fire();

        // Create database
        if ($this->option('create-db')) {
            $command = $this->getApplication()->find('db:create');

            $arguments = array(
                'command' => 'db:create',
                'db-name'   => $this->option('db-name'),
                '--host'    => $this->option('db-host'),
                '--port'    => $this->option('db-port'),
                '--user'    => $this->option('db-user'),
                '--password' => $this->option('db-password'),
                '--admin-user' => $this->option('db-admin-user'),
                '--admin-password' => $this->option('db-admin-password'),
            );

            $inputs = new ArrayInput($arguments);

            if ($command->run($inputs, $this->output) !== 0) {
                throw new Exception('Command db:create failed.');
            }
        }

        // 3. Install Craft CMS
        // Creates a dummy license.key file for  validations to pass.
        // This key needs to be deleted after installation. Otherwise, Craft
        // update and licensing functions won't work properly.
        // Creates a db.config in craft/config
        touch($this->getPath().'/craft/config/license.key');

        $this->generateDbConfig();

        $this->getApplication()->setCraftPath($this->getPath().'/craft');

        $craft = $this->getApplication()->bootstrap(true);

        //  The SERVER_NAME needs to defined for intallation to pass
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'Craft';
        }

        $installerParams = array(
            'username' => $this->option('admin-user'),
            'email' => $this->option('admin-email'),
            'password' => $this->option('admin-password'),
            'siteName' => $this->option('site-name'),
            'siteUrl' => $this->option('site-url'),
            'locale' => $this->option('locale')
        );

        $this->comment('Installing Craft...');

        $craft->setIsNotInstallingAfterNextCheck();

        $this->suppressOutput(function () use ($craft, $installerParams) {
            $craft->install->run($installerParams);
        });

        unlink($this->getPath().'/craft/config/license.key');

        $this->info('Craft installed.');
    }

    protected function generateDbConfig()
    {
        $handlebars = new Handlebars(array(
            'loader' => new FilesystemLoader(__DIR__.'/../templates/'),
        ));

        $destination = $this->getPath().'/craft/config/db.php';

        $handle = fopen($destination, 'w');

        $output = $handlebars->render('db.php', array(
            'host' => $this->option('db-host'),
            'port' => $this->option('db-port'),
            'name' => $this->option('db-name'),
            'user' => $this->option('db-user'),
            'password' => $this->option('db-password'),
        ));

        fwrite($handle, $output);

        fclose($handle);
    }
}
