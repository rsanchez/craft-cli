<?php

namespace CraftCli\Support;

class MysqlCommand extends AbstractMysqlCommand
{
    /**
     * Mysql query to execute
     * @var string
     */
    public $query;

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        $arguments = parent::getArguments();

        if ($this->query) {
            $arguments[] = '-e '.escapeshellarg($this->query);
        }

        return $arguments;
    }
}
