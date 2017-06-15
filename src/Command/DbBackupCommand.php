<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Input\InputArgument;

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
    protected function getArguments()
    {
        return array(
            array(
                'path',
                InputArgument::OPTIONAL,
                'Specify a path to write the backup to. Defaults to craft/storage/backups.',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $db = $this->craft->getComponent('db');

        $path = $this->suppressOutput([$db, 'backup']);

        $path = preg_replace('/^'.preg_quote(getcwd().DIRECTORY_SEPARATOR, '/').'/', '.'.DIRECTORY_SEPARATOR, $path);

        if ($this->argument('path')) {
            copy($path, $this->argument('path'));

            $path = $this->argument('path');
        }

        $this->info(sprintf('Backup %s created.', $path));
    }
}
