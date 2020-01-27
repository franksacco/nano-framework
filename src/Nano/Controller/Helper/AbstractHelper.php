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

/**
 * Helper for controller logic.
 *
 * @package Nano\Controller
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class AbstractHelper
{
    /**
     * @var AbstractController
     */
    protected $controller;

    /**
     * Initialize a controller helper.
     *
     * @param AbstractController $controller The controller object.
     */
    public function __construct(AbstractController $controller)
    {
        $this->controller = $controller;
    }
}
