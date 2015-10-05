<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * @version    0.0.15
 *
 * @link       TBA
 */

header('Content-Type: text/html; charset=utf-8');

/**
 * Check requiarments.
 */
if (version_compare("5.5", PHP_VERSION, '>')) {
    header('Content-Type: text/html; charset=utf-8');
    throw new \Exception(sprintf('Your server is running PHP version <b>%1$s</b>, but the system <b>%2$s</b> requires at least <b>%3$s</b> or higher</b>.', PHP_VERSION, "0.0.15", "5.5"));
}

/**
 * Minimum required extensions.
 */
if (!extension_loaded("mcrypt") || !extension_loaded("mbstring") || !extension_loaded("intl") || !extension_loaded("gd")) {
    throw new \Exception(sprintf('One or more of these <b>%1$s</b> required extensions are missing, please enable them.', implode(", ", ["mcrypt", "mbstring", "intl", "gd"])));
}

/**
 * Set global ENV. Used for debugging.
 */
if (isset($_SERVER['APPLICATION_ENV']) && $_SERVER["APPLICATION_ENV"] === 'development') {
    define("APP_ENV", 'development');
} else {
    define("APP_ENV", "production");
}

/**
 * Handle reporting level.
 */
error_reporting((APP_ENV === 'development' ? E_ALL : 0));

/**
 * Display of all other errors.
 */
ini_set("display_errors", (APP_ENV === 'development'));

/**
 * Display of all startup errors.
 */
ini_set("display_startup_errors", (APP_ENV === 'development'));

/**
 * Fixes files and server encoding.
 */
mb_internal_encoding('UTF-8');

/**
 * Some server configurations are missing a date timezone.
 */
if (ini_get('date.timezone') == '') {
    date_default_timezone_set('UTC');
}

/**
 * Hack CGI https://github.com/sitrunlab/LearnZF2/pull/128#issuecomment-98054110.
 */
if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

/**
 * This makes our life easier when dealing with paths. Everything is relative.
 * to the application root now.
 */
chdir(dirname(__DIR__));

/**
 * Setup autoloading.
 */
if (is_dir('vendor/zendframework') && is_file('vendor/autoload.php')) {
    $loader = include 'vendor/autoload.php';
}

if (!class_exists('Zend\Loader\AutoloaderFactory') || !is_file('config/autoload/db.local.php')) {
    if (!is_file('public/install.php')) {
        throw new \RuntimeException('Installation file is missing. Process cannot be started.');
    }
    header('Location: /install.php');
    return;
}

/**
 * Run the application!
 */
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
