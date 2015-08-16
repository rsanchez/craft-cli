<?php

namespace CraftCli\Command;

use Symfony\Component\Finder\Finder;

class DbBackupCommand extends BaseCommand
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
        $path = craft()->db->backup();

        $path = preg_replace('/^'.preg_quote(getcwd().DIRECTORY_SEPARATOR, '/').'/', '.'.DIRECTORY_SEPARATOR, $path);

        $this->info(sprintf('Backup %s created.', $path));
    }
}
