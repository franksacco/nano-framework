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
use Nano\Config\ConfigurationAwareTrait;
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
    use ConfigurationAwareTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ProviderInterface
     */
    protected $provider;

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
        $this->setConfiguration($config);
        $this->provider = $this->getDefaultProvider();
    }

    /**
     * Resolve the default user provider according to configuration.
     *
     * @return ProviderInterface Returns the default user provider.
     *
     * @throws UnexpectedValueException if the provider class not implements
     *     {@see ProviderInterface} interface.
     */
    protected function getDefaultProvider(): ProviderInterface
    {
        $provider = $this->getConfig('auth.provider');
        if (is_string($provider) && $this->container->has($provider)) {
            $provider = $this->container->get($provider);
        }

        if (! $provider instanceof ProviderInterface) {
            throw new UnexpectedValueException(sprintf(
                'User provider set in configuration not implements %s',
                ProviderInterface::class
            ));
        }

        return $provider;
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
    public function setProvider(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @inheritDoc
     */
    public function authenticateByAuthIdentifier(string $identifier): AuthenticableInterface
    {
        $user = $this->provider->getUserByAuthIdentifier($identifier);
        if ($user === null) {
            throw new NotAuthenticatedException(sprintf(
                'The user with identifier "%s" does not exist', $identifier
            ));
        }

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function authenticateByCredentials(string $identifier, string $secret): AuthenticableInterface
    {
        $user = $this->authenticateByAuthIdentifier($identifier);

        $hasher = new PasswordHasher($this->getConfig());
        if (! $hasher->check($secret, $user->getAuthSecret())) {
            throw new NotAuthenticatedException(sprintf(
                'Secret provided for user "%s" is not correct', $identifier
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