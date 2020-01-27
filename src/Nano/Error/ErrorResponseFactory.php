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

namespace Nano\Error;

use Laminas\Diactoros\Response\HtmlResponse;
use Nano\Controller\Exception\RenderingException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Twig\Error\Error;

/**
 * Default error response factory.
 *
 * @package Nano\Routing
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class ErrorResponseFactory implements ResponseFactoryInterface
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var array
     */
    private $data = [];

    /**
     * Initialize the error response factory.
     *
     * @param Environment $twig The Twig environment.
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Set data used inside an error template.
     *
     * @param array $data The data array.
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     *
     * @throws RenderingException if an error occur during template rendering.
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        switch ($code) {
            case 403:
            case 404:
                $template = 'error/404.html.twig';
                break;
            case 500:
                $template = 'error/500.html.twig';
                break;
            default:
                $template = 'index.html.twig';
        }

        try {
            $html = $this->twig->render($template, $this->data);

        } catch (Error $e) {
            throw new RenderingException(sprintf(
                'Unable to render an error template: %s',
                $e->getMessage()
            ), 0, $e);
        }

        return new HtmlResponse($html, $code);
    }
}
