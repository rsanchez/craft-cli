<?php

/**
 * Sample craft-cli Configuration file
 *
 * Rename this file to .craft-cli.php in your site root.
 */

// Quit if this is not being requested via the CLI
if (php_sapi_name() !== 'cli') {
    exit;
}

return array(
    /**
     * Craft base path
     *
     * Specify the path to your craft folder
     * If you leave this blank, it will assume your
     * folder is <current directory>/craft
     */
    'craft_path' => __DIR__.'/craft',

    /**
     * Craft app path
     *
     * Specify the path to your craft app folder
     * If you leave this blank, it will assume your
     * folder is <current directory>/craft/app
     */
    'craft_app_path' => null,

    /**
     * Craft framework path
     *
     * Specify the path to your Yii framework folder
     * If you leave this blank, it will assume your
     * folder is <current directory>/craft/app/framework
     */
    'craft_framework_path' => null,

    /**
     * Craft config path
     *
     * Specify the path to your craft config folder
     * If you leave this blank, it will assume your
     * folder is <current directory>/craft/config
     */
    'craft_config_path' => null,

    /**
     * Craft plugins path
     *
     * Specify the path to your craft plugins folder
     * If you leave this blank, it will assume your
     * folder is <current directory>/craft/plugins
     */
    'craft_plugins_path' => null,

    /**
     * Craft storage path
     *
     * Specify the path to your craft storage folder
     * If you leave this blank, it will assume your
     * folder is <current directory>/craft/storage
     */
    'craft_storage_path' => null,

    /**
     * Craft templates path
     *
     * Specify the path to your craft templates folder
     * If you leave this blank, it will assume your
     * folder is <current directory>/craft/templates
     */
    'craft_templates_path' => null,

    /**
     * Craft translations path
     *
     * Specify the path to your craft translations folder
     * If you leave this blank, it will assume your
     * folder is <current directory>/craft/translations
     */
    'craft_translations_path' => null,

    /**
     * Craft vendor path
     *
     * Specify the path to your craft vendor folder
     * If you leave this blank, it will assume your
     * folder is <current directory>/craft/app/vendor
     */
    'craft_vendor_path' => null,

    /**
     * Craft environment
     *
     * The server name of your craft environment.
     */
    'environment' => null,

    /**
     * Dotenv path
     *
     * (optional) If using phpdotenv, specify the path to
     * the directory containing your .env file, and
     * craft-cli load with load environment variables
     * from it
     */
    'dotenv_path' => __DIR__,

    /**
     * Custom commands
     *
     * An array of Command class names of
     * custom commands.
     */
    'commands' => array(
        #'\\Your\\Custom\\Command',
    ),

    /**
     * Custom command directories
     *
     * An array of directories, keyed by a namespace prefix,
     * which will be crawled for Command classes.
     */
    'commandDirs' => array(
        /*
        '\\Your\\Namespace' => '/path/to/commands',
        */
    ),
);
