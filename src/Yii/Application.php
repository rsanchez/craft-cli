<?php

namespace CraftCli\Yii;

use Craft\ConsoleApp;

class Application extends ConsoleApp
{
    /**
     * @var CraftCli\Yii\Application
     */
    protected static $globalApp;

    /**
     * This blows up the CLI normally,
     * so just override to return english for now
     *
     * @return string
     */
    public function getLanguage()
    {
        return 'en_US';
    }

    /**
     * Set the global app instance to be used by Craft CLI
     * @param CraftCli\Yii\Application $app
     * @return void
     */
    public static function setGlobalApp(Application $app)
    {
        self::$globalApp = $app;
    }

    /**
     * Get the global app instance used by Craft CLI
     * @return CraftCli\Yii\Application
     */
    public function getGlobalApp()
    {
        return self::$globalApp;
    }
}
