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
 * Interface implemented by entities that can be authenticated.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface AuthenticableInterface
{
    /**
     * Get the id of the user.
     *
     * @return string Returns the id if the user.
     */
    public function getId(): string;

    /**
     * Get the string that identify the user inside the application.
     *
     * For example, the identifier can be an email or a username.
     *
     * @return string Returns the identifier of the user.
     */
    public function getIdentifier(): string;

    /**
     * Get the user secret used for identification (e.g. password).
     *
     * The value of the secret can be in plaintext or hashed.
     *
     * @return string Returns the secret of the user.
     */
    public function getSecret(): string;
}
