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

class InstallCraftCommand extends Command implements ExemptFromBootstrapInterface
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
    protected function getArguments()
    {
        return array(
            array(
                'path',
                InputArgument::OPTIONAL,
                'Specify an installation path. Defaults to the current working directory.',
            ),
        );
    }

    /**
    * {@inheritdoc}
    */
    protected function getOptions()
    {
        return array(
            array(
                'terms', // name
                't', // shortcut
                InputOption::VALUE_NONE, // mode
                'I agree to the terms and conditions (https://buildwithcraft.com/license)', // description
                null, // default value
            ),
            array(
                'public', // name
                'p', // shortcut
                InputOption::VALUE_REQUIRED, // mode
                'Rename the public folder.', // description
                null, // default value
            ),
            array(
                'overwrite', // name
                'o', // shortcut
                InputOption::VALUE_NONE, // mode
                'Overwrite existing installation.', // description
                null, // default value
            ),
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
                'download-only', // name
                null, // shortcut
                InputOption::VALUE_NONE, // mode
                'Skip Craft CMS installer.', //description
            ),
            array(
                'skip-create-db', // name
                null, // shortcut
                InputOption::VALUE_NONE, // mode
                'Assume existing database.', //description
            ),

        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $start = microtime(true);

        $this->path = rtrim($this->argument('path'), DIRECTORY_SEPARATOR) ?: getcwd();

        try {
            // 1. Download Craft CMS
            //   $this->download();

            if ($this->option('download-only')) {
                return;
            }

            // 2. Create database
            if (!$this->option('skip-create-db')) {
                $command = $this->getApplication()->find('db:create');

                $arguments = array(
                    'command' => 'db:create',
                    '--host'    => $this->option('db-host'),
                    '--name'    => $this->option('db-name'),
                    '--port'    => $this->option('db-port'),
                    '--user'    => $this->option('db-user'),
                    '--password' => $this->option('db-password'),
                    '--admin-user' => $this->option('db-admin-user'),
                    '--admin-password' => $this->option('db-admin-password'),
                );

                $inputs = new ArrayInput($arguments);
                if ($command->run($inputs, $output) !== 0) {
                    throw new Exception("Command db:create failed.");
                }
            }

            // 3. Install Craft CMS
            // Creates a dummy license.key file for  validations to pass.
            // This key needs to be deleted after installation. Otherwise, Craft
            // update and licensing functions won't work properly.
            // Creates a db.config in craft/config
            exec("touch {$this->path}/craft/config/license.key", $output, $status);
            $this->generateDbConfig();

            $this->getApplication()->bootstrap();

            //  The SERVER_NAME needs to defined for intallation to pass
            $_SERVER['SERVER_NAME'] = '';
            $inputs = array(
                'username' => $this->option('admin-user'),
                'email' => $this->option('admin-email'),
                'password' => $this->option('admin-password'),
                'siteName' => $this->option('site-name'),
                'siteUrl' => $this->option('site-url'),
                'locale' => $this->option('locale')
            );
            craft()->install->run($inputs);

            exec("rm -f {$this->path}/craft/config/license.key", $output, $status);

        }
        catch(Exception $e) {
            $this->error($e->getMessage());
            return -1;
        }

        if ($this->showsDuration) {
            $output->writeln(sprintf('<info>Took %d seconds.</info>', microtime(true) - $start));
        }

        return;
    }

    protected function generateDbConfig() {
        $handlebars = new Handlebars(array(
            'loader' => new FilesystemLoader(__DIR__.'/../templates/'),
        ));

        $destination = $this->path.'/craft/config/db.php';

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


    /**
    * {@inheritdoc}
    */
    protected function download()
    {
        $path = $this->path;
        if (! is_dir($path) && ! @mkdir($path, 0777, true)) {
            $this->error(sprintf('Could not create directory %s.', $path));

            return;
        }

        // check terms and conditions
        if (! $this->option('terms') && ! $this->confirm('I agree to the terms and conditions (https://buildwithcraft.com/license)')) {
            $this->error('You did not agree to the terms and conditions (https://buildwithcraft.com/license)');

            return;
        }

        // check if craft is already installed, and overwrite option
        if (file_exists($path.'/craft') && ! $this->option('overwrite')) {
            $this->error('Craft is already installed here!');

            if (! $this->confirm('Do you want to overwrite?')) {
                $this->info('Exited without installing.');

                return;
            }
        }

        $url = 'https://buildwithcraft.com/latest.tar.gz?accept_license=yes';

        $this->comment('Downloading...');

        $downloader = new TempDownloader($url, '.tar.gz');

        $downloader->setOutput($this->output);

        $filePath = $downloader->download();

        $this->comment('Extracting...');

        $tarExtractor = new TarExtractor($filePath, $path);

        $tarExtractor->extract();

        // change the name of the public folder
        if ($public = $this->option('public')) {
            rename($path.'/public', $path.'/'.$public);
        }

        // Rename .htaccess
        rename($path.'/public/htaccess', $path.'/'.$public.'/.htaccess');

        $this->info('Installation complete!');
    }
}
