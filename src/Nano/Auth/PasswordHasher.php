<?php
/**
 * Nano Framework
 *
 * @package   Nano
 * @author    Francesco Saccani <saccani.francesco@gmail.com>
 * @copyright Copyright (c) 2019 Francesco Saccani
 * @version   1.0
 */

declare(strict_types=1);

namespace Nano\Auth;

use Nano\Config\ConfigurationInterface;

/**
 * Helper class for password hashing.
 *
 * Password hashing can be configured in the "passwords.php" configuration file.
 *
 * Available options for this class are:
 *
 * - "algorithm": a password algorithm constant denoting the algorithm to
 *   use when hashing the password; default: `PASSWORD_DEFAULT`.
 *
 * - "options": an associative array containing options. See documentation
 *   for information on the supported options for each algorithm. If omitted,
 *   the default cost will be used; default: `[]`.
 *
 * @see https://www.php.net/manual/en/book.password.php
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class PasswordHasher
{
    /**
     * @var int|string|null
     */
    private $algorithm;

    /**
     * @var array
     */
    private $options;

    /**
     * Initialize the password hashing helper.
     *
     * @param ConfigurationInterface $config The application settings.
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->algorithm = $config->get('passwords.algorithm', PASSWORD_DEFAULT);
        $this->options   = (array) $config->get('passwords.options', []);
    }

    /**
     * Get the algorithm used for password hashing.
     *
     * @return int|string|null Returns a password algorithm constant.
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    /**
     * Set the hash algorithm used by `password_hash()` function.
     *
     * @param int|string|null $algorithm A password algorithm constant.
     */
    public function setAlgorithm($algorithm)
    {
        $this->algorithm = $algorithm;
    }

    /**
     * Get the option list used for password hashing.
     *
     * @see https://www.php.net/manual/en/password.constants.php
     *
     * @return array Returns the option list.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set the hashing options used by `password_hash()` function.
     *
     * @see https://www.php.net/manual/en/password.constants.php
     *
     * @param array $options An option list.
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Returns information about the given hash.
     *
     * Returns an associative array with three elements:
     *  - `algo`: which will match a password algorithm constant;
     *  - `algoName`: which has the human readable name of the algorithm;
     *  - `options`: which includes the options provided when calling
     *   `password_hash()`.
     *
     * @param string $hash A hash created by `password_hash()`.
     * @return array Returns the information array.
     */
    public function getInfo(string $hash): array
    {
        return password_get_info($hash);
    }

    /**
     * Checks if the given hash matches the given password.
     *
     * @param string $password User plain text password.
     * @param string $hash Existing password hash to compare.
     * @return bool Returns `true` if the given password and hash match,
     *   `false` otherwise.
     */
    public function check(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Creates a password hash.
     *
     * @param string $password Plain text password to hash.
     * @return bool|string Returns the hashed password, or `false` on failure.
     */
    public function hash(string $password)
    {
        return password_hash($password, $this->algorithm, $this->options);
    }

    /**
     * Checks if the given hash should be rehashed to match the given
     * algorithm and options.
     *
     * @param string $hash A password hash.
     * @return bool Returns `true` if the hash should be rehashed, `false`
     *   otherwise.
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }
}