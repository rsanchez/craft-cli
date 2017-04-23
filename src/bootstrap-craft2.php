<?php

if (!isset($craftPath)) {
    foreach (array(
        './craft',
        '../../../../vendor/../craft',
    ) as $path) {
        if (is_dir($path.'/app')) {
            $craftPath = $path.'/app';
            break;
        }
    }

    if (!isset($craftPath)) {
        throw new Exception('Missing $craftPath. Set $craftPath before including this file.');
    }
}

$appPath = realpath($craftPath.'/app');

defined('CRAFT_APP_PATH') || define('CRAFT_APP_PATH', $appPath.'/');
defined('CRAFT_VENDOR_PATH') || define('CRAFT_VENDOR_PATH', CRAFT_APP_PATH.'vendor/');
defined('CRAFT_FRAMEWORK_PATH') || define('CRAFT_FRAMEWORK_PATH', CRAFT_APP_PATH.'framework/');

// The app/ folder goes inside craft/ by default, so work backwards from app/
defined('CRAFT_BASE_PATH') || define('CRAFT_BASE_PATH', realpath(CRAFT_APP_PATH.'..').'/');

// Everything else should be relative from craft/ by default
defined('CRAFT_CONFIG_PATH')       || define('CRAFT_CONFIG_PATH',       CRAFT_BASE_PATH.'config/');
defined('CRAFT_PLUGINS_PATH')      || define('CRAFT_PLUGINS_PATH',      CRAFT_BASE_PATH.'plugins/');
defined('CRAFT_STORAGE_PATH')      || define('CRAFT_STORAGE_PATH',      CRAFT_BASE_PATH.'storage/');
defined('CRAFT_TEMPLATES_PATH')    || define('CRAFT_TEMPLATES_PATH',    CRAFT_BASE_PATH.'templates/');
defined('CRAFT_TRANSLATIONS_PATH') || define('CRAFT_TRANSLATIONS_PATH', CRAFT_BASE_PATH.'translations/');

$directoryHelper = new CraftCli\Bootstrap\DirectoryHelper();

$directoryHelper->verifyDirectoryIsReadable(CRAFT_CONFIG_PATH, 'Craft Config Path');
$directoryHelper->verifyDirectoryIsWritable(CRAFT_STORAGE_PATH, 'Craft Storage Path');

$runtimePath = CRAFT_STORAGE_PATH.'runtime/';

try {
    $directoryHelper->verifyDirectoryExists($runtimePath);
} catch (Exception $e) {
    $directoryHelper->createDirectory($runtimePath, 'Craft Storage Runtime Path');
}

$directoryHelper->verifyDirectoryIsWritable($runtimePath, 'Craft Storage Runtime Path');

if (empty($isInstalling)) {
    try {
        $directoryHelper->verifyFileExists(CRAFT_CONFIG_PATH.'license.key');
    } catch (Exception $e) {
        throw new Exception('Missing license.key. Run the craft installer from your browser.');
    }
}

// Set the environment
defined('CRAFT_ENVIRONMENT') || define('CRAFT_ENVIRONMENT', 'localhost');

error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);
defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_TRACE_LEVEL') || define('YII_TRACE_LEVEL', 3);

require_once CRAFT_APP_PATH.'framework/yii.php';
require_once CRAFT_APP_PATH.'vendor/autoload.php';

Yii::$enableIncludePath = false;

require_once CRAFT_APP_PATH.'Craft.php';
require_once CRAFT_APP_PATH.'etc/console/ConsoleApp.php';
require_once CRAFT_APP_PATH.'Info.php';

Yii::setPathOfAlias('app', CRAFT_APP_PATH);
Yii::setPathOfAlias('plugins', CRAFT_PLUGINS_PATH);

$config = require_once CRAFT_APP_PATH.'etc/config/main.php';

$app = new CraftCli\Bootstrap\ConsoleApp($config, !empty($isInstalling));

CraftCli\Bootstrap\ConsoleApp::setInstance($app);

function craft()
{
    return CraftCli\Bootstrap\ConsoleApp::getInstance();
}

return $app;
