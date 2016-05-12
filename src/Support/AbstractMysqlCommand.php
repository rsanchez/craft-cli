<?php

namespace CraftCli\Support;

abstract class AbstractMysqlCommand
{
    /**
     * Mysql database name
     * @var string
     */
    public $db;

    /**
     * Mysql host
     * @var string
     */
    public $host;

    /**
     * Mysql user
     * @var string
     */
    public $user;

    /**
     * Mysql port
     * @var string
     */
    public $port;

    /**
     * Mysql password
     * @var string
     */
    public $password;

    /**
     * Constructor
     * @param string $db Mysql database name
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get base shell command being used
     * @return string mysql or mysqldump
     */
    protected function getBaseCommand()
    {
        return 'mysql';
    }

    /**
     * Get command line arguments for shell command
     * @return array
     */
    protected function getArguments()
    {
        $arguments = [];

        if ($this->password) {
            $arguments[] = 'MYSQL_PWD='.escapeshellarg($this->password);
        }

        $arguments[] = $this->getBaseCommand();

        if ($this->host) {
            $arguments[] = '-h '.escapeshellarg($this->host);
        }

        if ($this->port) {
            $arguments[] = '-P '.$this->port;
        }

        if ($this->user) {
            $arguments[] = '-u '.escapeshellarg($this->user);
        }

        return $arguments;
    }

    /**
     * Compile the shell command
     * @return string
     */
    public function __toString()
    {
        $arguments = $this->getArguments();

        array_push($arguments, $this->db);

        // space prefix to prevent bash history
        return ' '.implode(' ', $arguments);
    }
}
