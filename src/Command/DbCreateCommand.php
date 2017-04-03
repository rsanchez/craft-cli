<?php

namespace CraftCli\Command;

use CraftCli\Support\MysqlCommand;
use CraftCli\Support\MysqlCreateDatabaseCommand;
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
    protected function getArguments()
    {
        return array(
            array(
                'db-name',
                InputArgument::REQUIRED,
                'MySQL database name.',
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
        $this->comment('Testing database credentials...');

        if (! $this->testDbCredentials()) {
            throw new Exception('Could not connect to local mysql database.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->debug = $this->option('debug');

        $name = $this->argument('db-name');

        $this->dbCredentials = array(
            'host' => $this->option('host'),
            'port' => $this->option('port'),
            'name' => $name,
            'user' => $this->option('user'),
            'password' => $this->option('password'),
            'adminUser' => $this->option('admin-user'),
            'adminPassword' => $this->option('admin-password'),
        );

        try {
            $this->validate();
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }

        if ($this->testDbExists()) {
            return $this->fail(sprintf('Database %s already exists.', $name));
        }

        $this->comment(sprintf('Creating database %s...', $name));

        if (! $this->createDb()) {
            return $this->fail(sprintf('Failed to create database %s.', $name));
        }

        $this->info(sprintf('Database %s created.', $name));
    }

    /**
     * Make a MysqlCommand object
     * @param  string $query
     * @return \CraftCli\Support\MysqlCommand
     */
    protected function makeMysqlCommand($query)
    {
        $mysqlCommand = new MysqlCommand();

        if (! empty($this->dbCredentials['host'])) {
            $mysqlCommand->host = $this->dbCredentials['host'];
        }

        if (! empty($this->dbCredentials['adminUser'])) {
            $mysqlCommand->user = $this->dbCredentials['adminUser'];
        } else if (! empty($this->dbCredentials['user'])) {
            $mysqlCommand->user = $this->dbCredentials['user'];
        }

        if (! empty($this->dbCredentials['adminPassword'])) {
            $mysqlCommand->password = $this->dbCredentials['adminPassword'];
        } else if (! empty($this->dbCredentials['password'])) {
            $mysqlCommand->password = $this->dbCredentials['password'];
        }

        if (! empty($this->dbCredentials['port'])) {
            $mysqlCommand->port = $this->dbCredentials['port'];
        }

        if ($query) {
            $mysqlCommand->query = $query;
        }

        return $mysqlCommand;
    }

    /**
     * Make a MysqlCreateDatabaseCommand object
     * @return \CraftCli\Support\MysqlCreateDatabaseCommand
     */
    protected function makeMysqlCreateDatabaseCommand()
    {
        $mysqlCommand = new MysqlCreateDatabaseCommand();

        if (! empty($this->dbCredentials['host'])) {
            $mysqlCommand->host = $this->dbCredentials['host'];
        }

        if (! empty($this->dbCredentials['adminUser'])) {
            $mysqlCommand->adminUser = $this->dbCredentials['adminUser'];
        }

        if (! empty($this->dbCredentials['adminPassword'])) {
            $mysqlCommand->adminPassword = $this->dbCredentials['adminPassword'];
        }

        if (! empty($this->dbCredentials['user'])) {
            $mysqlCommand->user = $this->dbCredentials['user'];
        }

        if (! empty($this->dbCredentials['password'])) {
            $mysqlCommand->password = $this->dbCredentials['password'];
        }

        if (! empty($this->dbCredentials['port'])) {
            $mysqlCommand->port = $this->dbCredentials['port'];
        }

        if (! empty($this->dbCredentials['name'])) {
            $mysqlCommand->name = $this->dbCredentials['name'];
        }

        return $mysqlCommand;
    }

    /**
     * Test database credentials
     * @return boolean
     */
    protected function testDbCredentials()
    {
        $mysqlCommand = (string) $this->makeMysqlCommand('SHOW DATABASES');

        if ($this->debug) {
            $this->output->writeln($mysqlCommand);

            return true;
        }

        exec($mysqlCommand, $output, $status);

        return $status === 0;
    }

    /**
     * Test database credentials
     * @return boolean
     */
    protected function testDbExists()
    {
        $mysqlCommand = $this->makeMysqlCommand("SHOW DATABASES LIKE '{$this->dbCredentials['name']}'");

        $mysqlCommand->flags[] = '--batch';
        $mysqlCommand->flags[] = '--skip-column-names';
        $mysqlCommand->grep = $this->dbCredentials['name'];

        if ($this->debug) {
            $this->output->writeln((string) $mysqlCommand);

            return true;
        }

        exec($mysqlCommand, $output, $status);

        return !! $output;
    }

    /**
     * Create database
     * @return boolean
     */
    protected function createDb()
    {
        $mysqlCommand = (string) $this->makeMysqlCreateDatabaseCommand();

        if ($this->debug) {
            $this->output->writeln($mysqlCommand);

            return true;
        }

        exec($mysqlCommand, $output, $status);

        return $status === 0;
    }
}
