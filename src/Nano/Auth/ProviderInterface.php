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

/**
 * Interface implemented by a class that provides users for authentication.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface ProviderInterface
{
    /**
     * Retrieves a user identified inside the application by the given id.
     *
     * Usually the id corresponds with the value of the primary column in the
     * database user table.
     *
     * @param string $id The id of the user.
     * @return AuthenticableInterface|null Returns the user if exists, NULL
     *   otherwise.
     */
    public function getUserById(string $id): ?AuthenticableInterface;

    /**
     * Retrieves a user identified by the given string.
     *
     * Usually the identifier corresponds with an email or a custom username.
     *
     * @param string $username The username of the user.
     * @return AuthenticableInterface|null Returns the user if exists, NULL
     *   otherwise.
     */
    public function getUserByName(string $username): ?AuthenticableInterface;
}