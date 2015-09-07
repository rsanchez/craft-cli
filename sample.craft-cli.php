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
