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

namespace Nano\Controller\Helper;

use Nano\Controller\AbstractController;
use Nano\Controller\Exception\UnexpectedValueException;
use Nano\Session\SessionInterface;
use Nano\Session\SessionMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\TwigFunction;

/**
 * Controller helper for flash messages handling.
 *
 * @package Nano\Controller
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class FlashHelper extends AbstractHelper
{
    const SESSION_KEY = "flash-messages";

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @inheritDoc
     *
     * @throws UnexpectedValueException if a valid session is not provided.
     */
    public function __construct(AbstractController $controller)
    {
        parent::__construct($controller);
        $this->session = $this->getSession($controller->getRequest());

        $this->injectRenderFunctions($controller->getContainer());
    }

    /**
     * Retrieves the session instance from server request.
     *
     * @param ServerRequestInterface $request The server request.
     * @return SessionInterface Returns the session instance.
     *
     * @throws UnexpectedValueException if a valid session is not provided.
     */
    private function getSession(ServerRequestInterface $request): SessionInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if (! $session instanceof SessionInterface) {
            throw new UnexpectedValueException(sprintf(
                'Flash component error: %s instance required',
                SessionInterface::class
            ));
        }

        return $session;
    }

    /**
     * Add a new flash message.
     *
     * @param string $type The type of the message.
     * @param string $message The content of the message.
     */
    public function add(string $type, string $message)
    {
        $messages = $this->session->get(self::SESSION_KEY, []);
        $messages[] = [
            'type'    => $type,
            'message' => $message
        ];

        $this->session->set(self::SESSION_KEY, $messages);
    }

    /**
     * Shortcut to dynamically add a new flash message.
     *
     * `$this->flash->warning('message');` is the same as
     * `$this->flash->add('warning', 'message');`.
     *
     * @param string $name The name of the method.
     * @param array $arguments The argument list.
     *
     * @throws UnexpectedValueException if a valid message is not provided.
     */
    public function __call(string $name, array $arguments)
    {
        if (! isset($arguments[0])) {
            throw new UnexpectedValueException('Flash component error: a message is required');
        }

        $this->add($name, (string) $arguments[0]);
    }

    /**
     * Check if a flash message is present.
     *
     * @param string $type [optional] If set, is used to filter the type of messages.
     * @return bool
     */
    public function has(string $type = null): bool
    {
        return ! empty($this->get($type));
    }

    /**
     * Retrieve the list of flash messages.
     *
     * @param string $type [optional] If set, is used to filter the type of messages.
     * @return array Returns the list of messages where each item has the form
     *     ['type' => $type, 'message' => $message].
     */
    public function get(string $type = null): array
    {
        $messages = $this->session->get(self::SESSION_KEY, []);
        if ($type !== null) {
            array_filter($messages, function ($message) use ($type) {
                return $message['type'] === $type;
            });
        }

        return $messages;
    }

    /**
     * Retrieve only one flash messages.
     *
     * @param string $type [optional] If set, is used to filter the type of message.
     * @return array|null Returns the first message inserted in the form
     *     ['type' => $type, 'message' => $message] if exists, NULL otherwise.
     */
    public function getOne(string $type = null): ?array
    {
        $messages = $this->get($type);
        return empty($messages) ? null : $messages[0];
    }

    /**
     * Retrieve and delete the list of flash messages.
     *
     * @param string $type [optional] If set, is used to filter the type of messages.
     * @return array Returns the list of messages where each item has the form
     *     ['type' => $type, 'message' => $message].
     */
    public function consume(string $type = null): array
    {
        $messages = $this->session->get(self::SESSION_KEY, []);
        $new = [];
        if ($type === null) {
            $return = $messages;

        } else {
            $return = [];
            foreach ($messages as $message) {
                if ($message['type'] === $type) {
                    $return[] = $message;
                } else {
                    $new[] = $message;
                }
            }
        }

        $this->session->set(self::SESSION_KEY, $new);
        return $return;
    }

    /**
     * Retrieve and delete only one flash messages.
     *
     * @param string $type [optional] If set, is used to filter the type of message.
     * @return array|null Returns the first message inserted in the form
     *     ['type' => $type, 'message' => $message] if exists, NULL otherwise.
     */
    public function consumeOne(string $type = null): ?array
    {
        $messages = $this->session->get(self::SESSION_KEY, []);
        if ($type === null) {
            $return = array_shift($messages);
            $new    = $messages;

        } else {
            $return = $new = [];
            $found = false;
            foreach ($messages as $message) {
                if (!$found && $message['type'] === $type) {
                    $return = $message;
                    $found = true;

                } else {
                    $new[] = $message;
                }
            }
        }

        $this->session->set(self::SESSION_KEY, $new);
        return empty($return) ? null : $return;
    }

    /**
     * Inject function for flash messages manipulation in views.
     *
     * This method can be overwritten in order to adapt injection
     * according to the template rendering engine.
     *
     * @param ContainerInterface $container The DI container.
     */
    protected function injectRenderFunctions(ContainerInterface $container)
    {
        if ($container->has(Environment::class)) {
            /** @var Environment $twig */
            $twig = $container->get(Environment::class);
            $twig->addFunction(
                new TwigFunction('flash_has', [$this, 'has'])
            );
            $twig->addFunction(
                new TwigFunction('flash_get', [$this, 'get'])
            );
            $twig->addFunction(
                new TwigFunction('flash_get_one', [$this, 'getOne'])
            );
            $twig->addFunction(
                new TwigFunction('flash_consume', [$this, 'consume'])
            );
            $twig->addFunction(
                new TwigFunction('flash_consume_one', [$this, 'consumeOne'])
            );
        }
    }
}