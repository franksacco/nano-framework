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
use Nano\Config\ConfigurationInterface;
use Psr\Container\ContainerInterface;

/**
 * Basic implementation of an authentication guard.
 *
 * @package Nano\Auth
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class BasicGuard implements GuardInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * @var ProviderInterface
     */
    private $provider;

    /**
     * Initialize the guard class.
     *
     * @param ContainerInterface $container The DI container.
     * @param ConfigurationInterface $config The application settings.
     *
     * @throws UnexpectedValueException if the provider class not implements
     *   {@see ProviderInterface} interface.
     */
    public function __construct(ContainerInterface $container, ConfigurationInterface $config)
    {
        $this->container = $container;
        $this->config    = $config;

        $this->setProvider();
    }

    /**
     * @inheritDoc
     */
    public function getProvider(): ProviderInterface
    {
        return $this->provider;
    }

    /**
     * @inheritDoc
     */
    public function setProvider(ProviderInterface $provider = null)
    {
        if ($provider === null) {
            $provider = $this->config->get('auth.provider');
            if (is_string($provider) && $this->container->has($provider)) {
                $provider = $this->container->get($provider);
            }
            if (! $provider instanceof ProviderInterface) {
                throw new UnexpectedValueException(sprintf(
                    'User provider must implements %s',
                    ProviderInterface::class
                ));
            }
        }
        $this->provider = $provider;
    }

    /**
     * @inheritDoc
     */
    public function authenticateByUsername(string $username): AuthenticableInterface
    {
        $user = $this->provider->getUserByName($username);
        if ($user === null) {
            throw new NotAuthenticatedException(sprintf(
                'The user with username "%s" does not exist', $username
            ));
        }
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function authenticateByCredentials(string $username, string $secret): AuthenticableInterface
    {
        $user = $this->authenticateByUsername($username);

        $hasher = new PasswordHasher($this->config);
        if (! $hasher->check($secret, $user->getSecret())) {
            throw new NotAuthenticatedException(sprintf(
                'Secret provided for user "%s" is not correct', $username
            ));
        }
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function authenticateByToken(string $token): AuthenticableInterface
    {
        throw new NotAuthenticatedException('Authentication through token not supported');
    }
}
