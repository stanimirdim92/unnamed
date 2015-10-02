<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * @version    0.0.15
 *
 * @link       TBA
 */

namespace Application\Controller\Plugin;

use Zend\Math\Rand;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Db\Adapter\Adapter;
use Application\Exception\InvalidArgumentException;
use Application\Exception\RuntimeException;

final class Functions extends AbstractPlugin
{
    /**
     * @var Adapter $adapter
     */
    private $adapter = null;

    /**
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Execute plain mysql queries.
     *
     * @param string $query
     *
     * @throws InvalidArgumentException
     *
     * @return ResultSet|null
     */
    public function createPlainQuery($query)
    {
        if (empty($query)) {
            throw new InvalidArgumentException('Query must not be empty');
        }

        $stmt = $this->adapter->query((string) $query);
        $result = $stmt->execute();
        $result->buffer();

        if ($result->count() > 0 && $result->isQueryResult() && $result->isBuffered()) {
            return $result;
        }

        return;
    }

    /**
     * Never set the salt parameter for this function unless you are not a security expert who knows what he is doing.
     *
     * @link http://blog.ircmaxell.com/2015/03/security-issue-combining-bcrypt-with.html
     *
     * @param string $password the user password in plain text
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return string the encrypted password with the salt. Salt comes from password_hash
     */
    public static function createPassword($password)
    {
        if (empty($password)) {
            throw new InvalidArgumentException("Password cannot be empty");
        }

        if (static::strLength($password) < 8) {
            throw new InvalidArgumentException("Password must be atleast 8 characters long");
        }

        $pw = password_hash($password, PASSWORD_BCRYPT, ["cost" => 13]);

        if (empty($pw)) {
            throw new RuntimeException("Error while generating password");
        }

        return $pw;
    }

    /**
     * @param string $string The input string
     *
     * @return int The number of bytes
     */
    public static function strLength($string = null)
    {
        return mb_strlen($string, '8bit');
    }

    /**
     * Generate a random 48 chars long string via the OpenSSL|MCRYPT|M_RAND functions. and return a base64 encode of it.
     *
     * @return string
     */
    public static function generateToken()
    {
        return base64_encode(Rand::getBytes(48, true));
    }

    /**
     * Detect SSL/TLS protocol. If true activate cookie_secure key.
     *
     * @return bool
     */
    public static function isSSL()
    {
        if (isset($_SERVER['HTTPS'])) {
            if ('on' == strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS']) {
                return true;
            }
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        }
        return false;
    }
}
