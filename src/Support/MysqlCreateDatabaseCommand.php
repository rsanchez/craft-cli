<?php

namespace CraftCli\Support;

class MysqlCreateDatabaseCommand extends AbstractMysqlCommand
{
    /**
     * Mysql admin user
     * @var string
     */
    public $adminUser;

    /**
     * Mysql password
     * @var string
     */
    public $adminPassword;

    /**
     * Mysql database name
     * @var string
     */
    public $name;

    /**
     * {@inheritdoc}
     */
    protected function getUser()
    {
        return $this->adminUser ?: $this->user;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPassword()
    {
        return $this->adminPassword ?: $this->password;
    }

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        $arguments = parent::getArguments();

        $query = "CREATE DATABASE {$this->name};";

        if ($this->user) {
            $query .= " GRANT ALL PRIVILEGES ON {$this->name}.* To '{$this->user}'@'%' IDENTIFIED BY '{$this->password}'; FLUSH PRIVILEGES;";
        }

        $arguments[] = '-e '.escapeshellarg($query);

        return $arguments;
    }
}
