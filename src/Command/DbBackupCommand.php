<?php

namespace CraftCli\Command;

class DbBackupCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'db:backup';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Backup your database to <info>craft/storage</info>.';

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $db = $this->craft->getComponent('db');

        $path = $this->suppressOutput([$db, 'backup']);

        $path = preg_replace('/^'.preg_quote(getcwd().DIRECTORY_SEPARATOR, '/').'/', '.'.DIRECTORY_SEPARATOR, $path);

        $this->info(sprintf('Backup %s created.', $path));
    }
}
