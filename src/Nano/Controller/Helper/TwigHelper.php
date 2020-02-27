<?php
/**
 * Nano Framework
 *
 * @package   Nano
 * @author    Francesco Saccani <saccani.francesco@gmail.com>
 * @copyright Copyright (c) 2020 Francesco Saccani
 * @version   1.0
 */

declare(strict_types=1);

namespace Nano\Controller\Helper;

use Laminas\Diactoros\Response\HtmlResponse;
use Nano\Controller\AbstractController;
use Nano\Controller\Exception\RenderingException;
use Twig\Environment;
use Twig\Error\Error;

/**
 * Controller helper for Twig template engine.
 *
 * This helper inject automatically a response factory in the controller in
 * order to use Twig rendering engine when {@see AbstractController::render()}
 * is called.
 *
 * The created response has a status code set to 200 and the Content-Type
 * header set to "text/html".
 *
 * @package Nano\Controller
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
class TwigHelper extends AbstractHelper
{
    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $template = 'index.html.twig';

    /**
     * @var array
     */
    protected $context = [];

    /**
     * Initialize the Twig controller helper.
     *
     * @param AbstractController $controller The controller object.
     * @param Environment $twig The Twig environment set in the DI container.
     */
    public function __construct(AbstractController $controller, Environment $twig)
    {
        parent::__construct($controller);
        $this->twig = $twig;

        $this->controller->setResponseFactory(function () {
            return new HtmlResponse($this->render($this->template));
        });
    }

    /**
     * Set the name and the type of the template to be rendered.
     *
     * @param string $template The name of the template.
     */
    public function setTemplate(string $template)
    {
       $this->template = $template;
    }

    /**
     * Set context variables to be used inside a template.
     *
     * @param string|array $name The variable name or the list of variables.
     * @param mixed $value [optional] The variable value if name is a string,
     *     unused otherwise.
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $item) {
                $this->set($key, $item);
            }

        } elseif (is_string($name)) {
            $this->context[$name] = $value;
        }
    }

    /**
     * Render a template using Twig engine.
     *
     * @param string $template The name of the template.
     * @return string Returns the rendered template.
     *
     * @throws RenderingException if an error occur during rendering.
     */
    public function render(string $template): string
    {
        try {
            return $this->twig->render((string) $template, $this->context);

        } catch (Error $e) {
            throw new RenderingException(sprintf(
                'Unable to render the template "%s": %s',
                (string) $template, $e->getMessage()
            ), 0, $e);
        }
    }
}