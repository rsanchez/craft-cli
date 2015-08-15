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
     * Spoof $_SERVER variables
     *
     * This array will be merged with $_SERVER.
     *
     * When using php from the command line,
     * things like HTTP_HOST and DOCUMENT_ROOT
     * do not get set.
     *
     * Useful if you check for $_SERVER items
     * at runtime, like changing DB
     * credentials based on HTTP_HOST
     * in your config.php.
     *
     * You can also set these at the command line:
     *
     * HTTP_HOST="foo.dev" craft <command>
     */
    'server' => array(
        'SERVER_NAME' => getenv('SERVER_NAME') ?: 'localhost',
        'HTTP_HOST' => getenv('HTTP_HOST') ?: 'localhost',
        'DOCUMENT_ROOT' => getenv('DOCUMENT_ROOT') ?: __DIR__,
        'REQUEST_URI' => getenv('REQUEST_URI') ?: '/',
        'REMOTE_ADDR' => getenv('REMOTE_ADDR') ?: '127.0.0.1',
        'HTTP_USER_AGENT' => getenv('HTTP_USER_AGENT') ?: 'CraftCli',
    ),

    /**
     * Assign variables to config
     */
    'assign_to_config' => array(
        #'foo' => 'bar',
    ),

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
     * Event Callbacks
     *
     * An array of callback functions to be
     * invoked on the specified event.
     */
    'callbacks' => array(
        /*
        'bootstrap.before' => function ($app) {
        },
        'bootstrap.after' => function ($app) {
        },
        */
    ),

    /**
     * The default addon author name used when generating addons.
     */
    'addon_author_name' => '',

    /**
     * The default addon author URL used when generating addons.
     */
    'addon_author_url' => '',
);
