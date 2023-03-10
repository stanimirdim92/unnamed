<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * @version    0.0.21
 *
 * @link       TBA
 */

use Symfony\Component\Process\Process;

header('Content-Type: text/html; charset=utf-8');

if (version_compare("5.5", PHP_VERSION, '>')) {
    header('Content-Type: text/html; charset=utf-8');
    throw new \RuntimeException('Your server is running PHP version <b>%1$s</b> but Unnamed <b>%2$s</b> requires at least <b>%3$s</b> or higher</b>.', PHP_VERSION, "0.0.21", "5.5");
}

/**
 * Temporary increase execution time
 */
set_time_limit(1000);

/**
 * Change dir to point to root folder
 */
chdir(dirname(__DIR__));
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>System configuration file</title>
        <style type="text/css">
        </style>
    </head>
    <body>

<?php
$pageId = (isset($_GET["page"]) ? $_GET["page"] : 0);

/**
 * These directories are required
 */
$requiredDirs = [
    'data',
    'data/cache',
    'data/cache/frontend',
    'data/cache/modules',
    'data/logs',
    'data/translations',
    'data/database',
    'public/userfiles/captcha',
    'public/userfiles/images',
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    chmod($dir, 0750);
}

switch ((int) $pageId) {
    case 1:
        /**
         * Check composer install directory
         */
        if (!is_file("composerInstalation/vendor/autoload.php")) {
            echo "<p>Began extracting composer.</p>";
            if (!is_dir("composerInstalation/")) {
                mkdir("composerInstalation/", 0750, true);
            }
            $composerPhar = new Phar("Composer.phar");
            $composerPhar->extractTo("composerInstalation/");
            chmod("composerInstalation/", 0750);

            echo "<p>Extraction was successful.</p>";
        } else {
            echo "<p>File <b>autoload.php</b> already exists. Skipping composer installation.</p>";
        }

        /**
         * Check for vendor folder
         */
        if (!is_file("vendor/autoload.php")) {
            echo "<p>Validating composer.json</p>";

            if (!json_decode(file_get_contents("composer.json"))) {
                throw new \RuntimeException("<p>It looks like composer.json is invalid.<br> Terminating installation.</p>");
            }
            echo "<p>Validation was successful</p>";
            echo "<p>Installing composer packagelist.</p>";

            require 'composerInstalation/vendor/autoload.php';

            $install = new Process(sprintf('composer self-update'), getcwd(), null, null, 1000);
            $install->run();

            $install = new Process(sprintf('composer install --optimize-autoloader'), getcwd(), null, null, 1000);
            $install->run();

            if (!$install->isSuccessful()) {
                echo "<pre>".print_r($install->getErrorOutput(), true)."</pre>";
            }

            if (!is_file("vendor/autoload.php")) {
                throw new \RuntimeException("<p>Something is wrong. File is still missing after installation. <a href='/install.php'>Reinstall</a></p>");
            }
            echo "<p>Installation done.</p>";
        } else {
            echo "<p>Your vendor folder already exists.</p>";
        }

        /**
         * Check for database setup
         */
        if (!is_file('config/autoload/doctrine.local.php')) {
            echo "<p><a href='/install.php?page=2'>Setup a database</a></p>";
        } else {
            echo "<p>Your database configuration file has already been setup</p>";
            echo "<p><a href='/'>Back</a></p>";
        }

        break;
    case 2:
        if (!is_file('config/autoload/doctrine.local.php')) {
            ?>
            <form id="databaseSetup" method="post" action="#">
                <label for="name">Database &#42;
                    <input type="text" size="35" id="dbname" name="dbname" required="required" placeholder="The name of the database">
                </label>
                <label for="username">Username &#42;
                    <input type="text" size="35" id="username" name="username" required="required" placeholder="The connection username">
                </label>
                <label for="password">Password
                    <input type="password" size="35" id="password" name="password" placeholder="The connection password">
                </label>
                <label for="host">Hostname
                    <input type="text" size="35" id="host" name="host" placeholder="The IP address or hostname to connect to">
                </label>
                <label for="port">Port
                    <input type="text" size="35" id="port" name="port" placeholder="The port to connect to (if applicable)">
                </label>
                <input type="submit" id="submit" name="submit" value="Configure database">
            </form>
<?php
if (!empty($_POST["submit"]) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    /**
     * htmlspecialchars fix|hack. Well done PHP, well done...
     */
    define('CHARSET', 'UTF-8');
    define('REPLACE_FLAGS', ENT_QUOTES | ENT_SUBSTITUTE);

    function filter($string = null)
    {
        return htmlspecialchars($string, REPLACE_FLAGS, CHARSET);
    }

    $dbSetup = [
        'doctrine' => [
            'connection' => [
                'orm_default' => [
                    'driverClass' =>'Doctrine\DBAL\Driver\PDOMySql\Driver',
                    'params' => [
                        'host'     => filter($_POST["host"]),
                        'port'     => filter($_POST["port"]),
                        'user'     => filter($_POST["username"]),
                        'password' => filter($_POST["password"]),
                        'dbname'   => filter($_POST["dbname"]),
                    ],
                ],
            ],
        ],
    ];

    file_put_contents("config/autoload/doctrine.local.php", '<?php return ' . var_export($dbSetup, true).';'.PHP_EOL);
    header("Location: /");
    return;
}
        } else {
            throw new \RuntimeException("Your database configuration has already been setup");
        }
        break;
    default:
        echo '<p><a href="/install.php?page=1">Start intallation</a></p>';
        break;
}
?>
    </body>
</html>
