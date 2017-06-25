# Craft CLI

Command line interface for Craft CMS.

## Installation

If you are on Mac, you should install via [Homebrew](http://brew.sh/).

```
brew tap rsanchez/homebrew-craft-cli
brew install craft-cli
```

Otherwise, you should download the phar: https://github.com/rsanchez/craft-cli/releases/latest

```
php craft.phar <your:command>
```

See [Composer Installation](#composer-installation) for alternate ways to install.

## Usage

If you are using a multi-environment config, you must specify your environment either using the `--environment=` flag on your commands, or set the `CRAFT_ENVIRONMENT` env variable.

```
craft --environment="mysite.dev" show:config
```

```
CRAFT_ENVIRONMENT="mysite.dev" craft show:config
```

Craft CLI is [dotenv](https://github.com/vlucas/phpdotenv) aware, and will automagically load environment variables from a `.env` file in your project root (path can be customised via `.craft-cli.php`). You may set your Craft environment by setting a `CRAFT_ENVIRONMENT` variable in your `.env` file.

## Commands

- [`assets:pull`](#assets-pull)
- [`assets:push`](#assets-push)
- [`clear:cache`](#clear-cache)
- [`console`](#console)
- [`db:backup`](#db-backup)
- [`db:create`](#db-create)
- [`db:pull`](#db-pull)
- [`db:push`](#db-push)
- [`db:restore`](#db-restore)
- [`download`](#download-craft)
- [`generate:command`](#generate-command)
- [`help`](#help)
- [`init`](#init)
- [`install`](#install-craft)
- [`install:plugin`](#install-plugin)
- [`list`](#list)
- [`rebuild:searchindexes`](#rebuild-search-indexes)
- [`run:tasks`](#run-tasks)
- [`show:config`](#show-config)
- [`tail`](#tail)
- [`update:assetsindexes`](#update-asset-indexes)

### Assets Pull

Pull Asset files from a remote environment.

```
craft assets:pull --ssh-host=your.remote.server.com --ssh-user=yourUserName yourRemoteEnvironmentName
```

### Assets Push

Push Asset files to a remote environment

```
craft assets:push --ssh-host=your.remote.server.com --ssh-user=yourUserName yourRemoteEnvironmentName
```

### Clear Cache

Clear all Craft caches.

```
craft clear:cache
```

Select which cache(s) to clear from an interactive list.

```
craft clear:cache -s
```

### Console

Start an interactive shell.

```
craft console
```

### DB Backup

Backup your database to `craft/storage/backups`.

```
craft db:backup
```

Backup your database to the specified path.

```
craft db:backup ./backup.sql
```

### DB Create

Create a database

```
craft db:create --host=localhost --port=3306 --name=yourDbName --user=yourUserName --password=yourPassword
```

### DB Pull

Pull a remote database to the local database.

```
craft db:pull --ssh-host=your.remote.server.com --ssh-user=yourUserName --force yourRemoteEnvironmentName
```

### DB Push

Push your local database to a remote database.

```
craft db:push --ssh-host=your.remote.server.com --ssh-user=yourUserName --force yourRemoteEnvironmentName
```

### DB Restore

Restore the database from the most recent backup from `craft/storage/backups`.

```
craft db:restore --force
```

Restore the database from the specified `.sql` file.

```
craft db:restore --force ./backup.sql
```

### Download Craft

Download Craft to the current directory.

```
craft download
```

Create the specified directory and download Craft into it.

```
craft download path/to/directory
```

### Generate Command

Generate a custom command file in the specified directory.

```
craft generate:command your:custom_command ./commands/
```

Generate a custom command file with a namespace.

```
craft generate:command --namespace="YourSite\Command" your:custom_command ./src/YourSite/Command/
```

Generate a custom command with arguments and options.

```
craft generate:command --options --arguments your_command ./commands/
```

### Help

Display information about a command and its arguments/options.

```
craft help <command>
```

### Init

Create an `.craft-cli.php` config file in the current directory

```
craft init
```

This config file is only necessary if you if you are using [Custom Commands](#custom-commands) or have renamed your `craft` folder.

### Install Craft

Download and install Craft to the current directory.

```
craft install
```

Create the specified directory and install Craft into it.

```
craft install path/to/directory
```

### Install Plugin

Install a plugin from a GitHub repository.

```
craft install:plugin pixelandtonic/ElementApi
```

### List

List the available commands.

```
craft list
```

### Rebuild Search Indexes

```
craft rebuild:searchindexes
```

### Run Tasks

Run all pending tasks.

```
craft run:tasks
```

Reset "running" (stalled) tasks and then run all tasks.

```
craft run:tasks --reset-running
```

Reset failed tasks and then run all tasks.

```
craft run:tasks --reset-failed
```

### Show Config

Show all config items.

```
craft show:config
```

Show the specified config item.

```
craft show:config db.user
```

### Tail

Show a tail of craft.log

```
craft tail
```

### Update Asset Indexes

```
craft update:assetsindexes
```

## Custom Commands

Craft CLI custom commands are [Symfony Console](http://symfony.com/doc/current/components/console/introduction.html) Command objects. You can add custom commands to your `.craft-cli.php` config file by adding the class name to the `commands` array, or by adding a folder path to the `commandDirs` array.

You can generate a custom command file using the `craft generate:command` command.

## Troubleshooting

### Your command-line PHP cannot connect to MySQL

You can test this by running this at the command line (change the DB credentials to your actual credentials):

```
php -r "var_dump(@mysql_connect('hostname', 'username', 'password', 'database_name'));"
```

If this prints false, then you know that your CLI PHP is not configured to connect to your database. This is frequently caused by an incorrect default MySQL socket setting.

If you are running MAMP, for instance, and are using the stock Mac OS command-line PHP, you will not be able to connect out-of-the-box. You will need to edit your `/etc/php.ini` (or wherever your php.ini file is located) file and change the `mysql.default_socket` and/or the `mysqli.default_socket` to `/Applications/MAMP/tmp/mysql/mysql.sock`.

## Composer Installation

You can install globally:

```
composer global require craft-cli/cli
```

Make sure your global composer installation is added to your PATH in your `~/.bash_profile` (or `~/.profile` or `~/.bashrc` or `~/.zshrc`) so that you may run the binary from the command line:

```
export PATH=~/.composer/vendor/bin:$PATH
```

Or, you can install on a per project basis, rather than globally to your host system.

```
composer require craft-cli/cli
```

Then the command would be found in your `vendor/bin` folder, so you'd run this at your command line:

```
vendor/bin/craft <your:command>
```
