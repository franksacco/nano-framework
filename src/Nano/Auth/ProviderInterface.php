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
     * Retrieves a user identified by the given string.
     *
     * Usually the identifier corresponds with an email or a custom username.
     *
     * @param string $identifier The user identifier used for authentication.
     * @return AuthenticableInterface|null Returns the user if exists, `null`
     *   otherwise.
     */
    public function getUserByAuthIdentifier(string $identifier): ?AuthenticableInterface;
}