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

namespace Nano\Session;

use Nano\Session\Exception\ConfigurationException;
use Nano\Session\Exception\SessionException;

/**
 * Provide an Object Oriented interface for session management.
 *
 * @package Nano\Session
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface SessionInterface
{
    /**
     * Get current session name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get current session id.
     *
     * If the session is not already started, this method returns an empty string.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Checks whether the session is active or not.
     *
     * @return bool Return TRUE if session is active, FALSE otherwise.
     */
    public function isActive(): bool;

    /**
     * Set the session ini options.
     *
     * @param array $iniOptions The list of ini options to set in the form
     *   `name` => `value`, where `name` key should not include the "session."
     *   prefix.
     *
     * @throws ConfigurationException if a session ini option fails to be set.
     */
    public function configure(array $iniOptions);

    /**
     * Initialize session data.
     *
     * @throws SessionException if an error occurs.
     */
    public function start();

    /**
     * Regenerate session ID and optionally delete the old session file.
     *
     * If the session is not active, this method should start it.
     *
     * @param bool $deleteOld [optional] Whether to delete the old session file
     *   or not; default: FALSE.
     *
     * @throws SessionException if an error occurs.
     */
    public function regenerate(bool $deleteOld = false);

    /**
     * Delete all data registered to this session and close session.
     *
     * This method deletes data that is stored in the session storage and then
     * close the session.
     * Note that this method does not delete session cookie.
     *
     * If the session is not active, this method should start it.
     *
     * @param bool $deleteCookie [optional] Whether to delete the session
     *   cookie or not; default: FALSE.
     *
     * @throws SessionException if an error occurs.
     */
    public function destroy(bool $deleteCookie = false);

    /**
     * Write session data and end session.
     *
     * If the session is not active, this method should do nothing.
     */
    public function close();

    /**
     * Force the execution of session garbage collection.
     *
     * By default, GC is done at session start based on probability.
     * Therefore, it is recommended to execute GC periodically for production
     * systems using, e.g., "cron" for UNIX-like systems.
     *
     * @throws SessionException if an error occurs.
     */
    public function gc();

    /**
     * Set the session cookie parameters.
     *
     * @param int $lifetime Lifetime of the session cookie, defined in seconds.
     *   <b>$lifetime = 0</b> means that session ID cookie is deleted
     *   immediately when browser is terminated.
     * @param string|null $path [optional] The path on the server in which the
     *   cookie will be available on.
     * @param string|null $domain [optional] The (sub)domain that the cookie
     *   is available to.
     * @param bool $secure [optional] When TRUE the cookie will only be
     *   transmitted over a secure HTTPS connection.
     * @param bool $httpOnly [optional] When TRUE the cookie will be made
     *   accessible only through the HTTP protocol.
     * @param string|null $sameSite [optional] When "Strict" prevents the
     *   browser from sending this cookie along with cross-site requests, when
     *   is "Lax" only some cross-site usage is allowed.
     */
    public function setCookieParams(int $lifetime,
                                    ?string $path = null,
                                    ?string $domain = null,
                                    bool $secure = false,
                                    bool $httpOnly = false,
                                    ?string $sameSite = null);

    /**
     * Get the session cookie parameters.
     *
     * The returned array contains the following items:
     *  - 'expires': the Unix timestamp when the cookie expires.
     *  - 'path': the path where information is stored.
     *  - 'domain': the domain of the cookie.
     *  - 'secure': the cookie should only be sent over secure connections.
     *  - 'httpOnly': the cookie can only be accessed through the HTTP protocol.
     *  - 'sameSite': prevents the browser from sending this cookie along with
     *   cross-site requests.
     *
     * @return array Returns the session cookie parameters.
     */
    public function getCookieParams(): array;

    /**
     * Checks whether a session variable exists or not.
     *
     * Use '.' to access array elements.
     *
     * @param string $key The session key name.
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Retrieve a variable stored in the session.
     *
     * Use '.' to access array elements.
     *
     * @param string $key The name of the session key.
     * @param mixed $default [optional] The value returned if key is not set.
     * @return mixed Returns associated data if the key is set,
     *   <b>$default</b> otherwise.
     */
    public function get(string $key, $default = null);

    /**
     * Store a variable in the session.
     *
     * Use '.' to access array elements.
     *
     * @param string $key The name of the session key.
     * @param mixed $value The data to store at given key.
     */
    public function set(string $key, $value);

    /**
     * Remove a variable from the session.
     *
     * @param string $key The name of the session key.
     */
    public function remove(string $key);

    /**
     * Free all session variables.
     */
    public function clear();
}
