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

use Nano\Auth\Exception\NotAuthenticatedException;
use Nano\Auth\Exception\UnexpectedValueException;

/**
 * Define rules for user authentication.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
interface GuardInterface
{
    /**
     * Get the user provider used by this guard.
     *
     * @return ProviderInterface Returns the user provider.
     */
    public function getProvider(): ProviderInterface;

    /**
     * Set the user provider used by this guard.
     *
     * If this method is called without the parameter, the provider class
     * set in the "auth.provider" configuration key is used.
     *
     * @param ProviderInterface $provider [optional] The user provider.
     *
     * @throws UnexpectedValueException if the provider class not implements
     *     {@see ProviderInterface} interface.
     */
    public function setProvider(ProviderInterface $provider = null);

    /**
     * Try to authenticate an user with only the given username.
     *
     * Actually, this method does not perform a real authentication but it can
     * be used to verify that the username corresponds to a valid user.
     *
     * @param string $username The provided username (e.g. email).
     * @return AuthenticableInterface Returns the user object.
     *
     * @throws NotAuthenticatedException if authentication is not successful.
     */
    public function authenticateByUsername(string $username): AuthenticableInterface;

    /**
     * Try to authenticate an user with the given credentials.
     *
     * @param string $username The provided username (e.g. email).
     * @param string $secret The provided user secret (e.g. password).
     * @return AuthenticableInterface Returns the user object.
     *
     * @throws NotAuthenticatedException if authentication is not successful.
     */
    public function authenticateByCredentials(string $username, string $secret): AuthenticableInterface;

    /**
     * Try to authenticate an user with the given token.
     *
     * This type of authentication is thought for the case where a user is
     * maintained signed in through a long-term token, e.g. a token stored in
     * a cookie for persistent login.
     * If this guard not offers this functionality, it is possible to
     * implements this method only throwing a NotAuthenticatedException.
     *
     * @param string $token The provided token.
     * @return AuthenticableInterface Returns the user object.
     *
     * @throws NotAuthenticatedException if authentication is not successful.
     */
    public function authenticateByToken(string $token): AuthenticableInterface;
}
