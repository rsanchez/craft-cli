<?php

namespace CraftCli\Support;

class MysqlDumpCommand extends AbstractMysqlCommand
{
    /**
     * {@inheritdoc}
     */
    protected function getBaseCommand()
    {
        return 'mysqldump';
    }
}
