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
     * Craft path
     *
     * Specify the path to your craft folder
     * If you leave this blank, it will assume your
     * folder is <current directory>/craft
     */
    'craft_path' => __DIR__.'/craft',

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
     * Craft environment
     *
     * The server name of your craft environment.
     */
    'environment' => null,

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
