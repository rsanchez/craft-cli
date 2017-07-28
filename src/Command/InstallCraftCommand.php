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
use Symfony\Component\Console\Question\Question;

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
                'en_us', // default value
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
        $errors = $this->validateOptions();

        if ($errors) {
            if ($this->option('no-prompt')) {
                foreach ($errors as $error) {
                    $this->error($error);
                }

                return 1;
            }

            $this->promptForOptions();
        }

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

        parent::fire();

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
            'locale' => $this->option('locale'),
        );

        $this->comment('Installing Craft...');

        $craft->setIsNotInstallingAfterNextCheck();

        $this->suppressOutput(function () use ($craft, $installerParams) {
            $craft->install->run($installerParams);
        });

        unlink($this->getPath().'/craft/config/license.key');

        $this->info('Craft installed.');
    }

    protected function validateOptions()
    {
        $errors = [];

        $requiredOptions = array(
            'db-host',
            'db-name',
            'db-user',
            'db-password',
            'site-name',
            'site-url',
            'admin-user',
            'admin-password',
            'admin-email',
            'locale',
        );

        foreach ($requiredOptions as $option) {
            if (!$this->option($option)) {
                $errors[] = sprintf('--%s is required', $option);
            }
        }

        return $errors;
    }

    protected function promptForOption($question, $option, $allowEmpty = false)
    {
        do {
            $question = new Question($question, $this->option($option));

            if ($allowEmpty) {
                $question->setValidator(function ($value) {
                    return $value;
                });
            }

            $value = $this->output->askQuestion($question, $this->option($option));
        } while (!$value && !$allowEmpty);

        $this->input->setOption($option, $value);

        return $value;
    }

    protected function promptForOptions()
    {
        $this->promptForOption('Database server name or IP address', 'db-host');
        $this->promptForOption('Database name', 'db-name');
        $this->promptForOption('Database user', 'db-user');
        $this->promptForOption('Database password', 'db-password');
        $this->promptForOption('Database port', 'db-port', true);

        $createDb = $this->confirm('Do you want want to create this database?');

        $this->input->setOption('create-db', $createDb);

        if ($createDb) {
            $adminDbUser = $this->promptForOption('What is your admin database user? (optional, only if different from your site database user)', 'db-admin-user', true);

            if ($adminDbUser) {
                $this->promptForOption('What is your admin database password?', 'db-admin-password');
            }
        }

        $this->promptForOption('Site Name', 'site-name');
        $this->promptForOption('Site URL', 'site-url');
        $this->promptForOption('Craft admin username', 'admin-user');
        $this->promptForOption('Craft admin password', 'admin-password');
        $this->promptForOption('Craft admin email', 'admin-email');

        $locale = $this->askWithCompletion(
            'Default Locale',
            array('ar', 'ar_sa', 'bg', 'bg_bg', 'ca_es', 'cs', 'cy_gb', 'da', 'da_dk', 'de', 'de_at', 'de_ch', 'de_de', 'el', 'el_gr', 'en', 'en_as', 'en_au', 'en_bb', 'en_be', 'en_bm', 'en_bw', 'en_bz', 'en_ca', 'en_dsrt', 'en_dsrt_us', 'en_gb', 'en_gu', 'en_gy', 'en_hk', 'en_ie', 'en_in', 'en_jm', 'en_mh', 'en_mp', 'en_mt', 'en_mu', 'en_na', 'en_nz', 'en_ph', 'en_pk', 'en_sg', 'en_shaw', 'en_tt', 'en_um', 'en_us', 'en_us_posix', 'en_vi', 'en_za', 'en_zw', 'en_zz', 'es', 'es_cl', 'es_es', 'es_mx', 'es_us', 'es_ve', 'et', 'fi', 'fi_fi', 'fil', 'fr', 'fr_be', 'fr_ca', 'fr_ch', 'fr_fr', 'fr_ma', 'he', 'hr', 'hr_hr', 'hu', 'hu_hu', 'id', 'id_id', 'it', 'it_ch', 'it_it', 'ja', 'ja_jp', 'ko', 'ko_kr', 'lt', 'lv', 'ms', 'ms_my', 'nb', 'nb_no', 'nl', 'nl_be', 'nl_nl', 'nn', 'nn_no', 'no', 'pl', 'pl_pl', 'pt', 'pt_br', 'pt_pt', 'ro', 'ro_ro', 'ru', 'ru_ru', 'sk', 'sl', 'sr', 'sv', 'sv_se', 'th', 'th_th', 'tr', 'tr_tr', 'uk', 'vi', 'zh', 'zh_cn', 'zh_tw'),
            'en_us'
        );

        $this->input->setOption('locale', $locale);
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
