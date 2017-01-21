<?php

namespace CraftCli\Command;

use CraftCli\Support\SshCommand;
use CraftCli\Support\MysqlCommand;
use CraftCli\Support\MysqlDumpCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Exception;

class DbCreateCommand extends Command implements ExemptFromBootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'db:create';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a database';

    /**
     * Database credentials
     * @var array
     */
    protected $dbCredentials;

    /**
     * Debugging
     * @var bool
     */
    protected $debug;

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return array(
            array(
                'host', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'MySQL host.', // description
                'localhost', // default value
            ),
            array(
                'port', // name
                null, // shortcut
                InputOption::VALUE_OPTIONAL, // mode
                'MySQL port.', // description
                3306, // default value
            ),
            array(
                'name', // name
                null, // shortcut
                InputOption::VALUE_REQUIRED, // mode
                'MySQL database name.', // description
                null, // default value
            ),
            array(
                'user', // name
                null, // shortcut
                InputOption::VALUE_REQUIRED, // mode
                'MySQL username.', // description
                null, // default value
            ),
            array(
                'password', // name
                null, // shortcut
                InputOption::VALUE_REQUIRED, // mode
                'MySQL password.', // description
                null, // default value
            ),
            array(
                'admin-user',
                null,
                InputOption::VALUE_OPTIONAL,
                'MySQL administrative user.',
            ),
            array(
                'admin-password',
                null,
                InputOption::VALUE_OPTIONAL,
                'MySQL administrative password.',
                null,
            ),
            array(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'Print the command rather than executing it.',
                null
            ),
        );
    }

    /**
     * Validate environments/config
     * @throws \Exception
     * @return void
     */
    protected function validate()
    {
        $this->debug = $this->option('debug');

        $adminUser = $this->option('admin-user') ? $this->option('admin-user') : $this->option('user');
        $adminPassword = $this->option('admin-password') ? $this->option('admin-password') : $this->option('password');
        $this->dbCredentials = array(
            'host' => $this->option('host'),
            'port' => $this->option('port'),
            'name' => $this->option('name'),
            'user' => $this->option('user'),
            'password' => $this->option('password'),
            'adminUser' => $adminUser,
            'adminPassword' => $adminPassword
        );

        $this->info('Testing database credentials...');

        if (! $this->testDbCredentials()) {
            throw new Exception('Could not connect to local mysql database.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return -1;
        }

        $this->info('Creating database...');

        if (! $this->createDb()) {
            $this->error('Failed to create database.');
            return -1;
        }

        $this->info('Database created.');
    }

    /**
     * Make a MysqlCommand object
     * @param  string $class
     * @param  array  $credentials
     * @param  string $query
     * @return \CraftCli\Support\AbstractMysqlCommand
     */
    protected function makeMysqlCommand($class, $credentials, $query = null)
    {
        $mysqlCommand = new $class('');

        if (! empty($credentials['host'])) {
            $mysqlCommand->host = $credentials['host'];
        }

        if (! empty($credentials['adminUser'])) {
            $mysqlCommand->user = $credentials['adminUser'];
        }

        if (! empty($credentials['adminPassword'])) {
            $mysqlCommand->password = $credentials['adminPassword'];
        }

        if (! empty($credentials['port'])) {
            $mysqlCommand->port = $credentials['port'];
        }

        if ($query) {
            $mysqlCommand->query = $query;
        }

        return $mysqlCommand;
    }

    /**
     * Test database credentials
     * @return boolean
     */
    protected function testDbCredentials()
    {
        $mysqlCommand = (string) $this->makeMysqlCommand(MysqlCommand::class, $this->dbCredentials, 'SHOW DATABASES');

        if ($this->debug) {
            $this->output->writeln($mysqlCommand);

            return true;
        }

        exec($mysqlCommand, $output, $status);

        return $status === 0;
    }

    /**
     * Create database
     * @return boolean
     */
    protected function createDb()
    {
        $mysqlCommand = (string) $this->makeMysqlCommand(MysqlCommand::class, $this->dbCredentials, "CREATE DATABASE {$this->dbCredentials['name']}; GRANT ALL PRIVILEGES ON {$this->dbCredentials['name']}.* To '{$this->dbCredentials['user']}'@'%' IDENTIFIED BY '{$this->dbCredentials['password']}'; FLUSH PRIVILEGES;");

        if ($this->debug) {
            $this->output->writeln($mysqlCommand);

            return true;
        }

        exec($mysqlCommand, $output, $status);

        return $status === 0;
    }
}
