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

namespace Nano\Controller\Form;

use Rakit\Validation\Validation;
use Rakit\Validation\Validator;

/**
 * Abstract class for form validation and execution.
 *
 * @see https://github.com/rakit/validation
 *
 * @package Nano\Controller
 * @author  Francesco Saccani <saccani.francesco@gmail.com>
 */
abstract class AbstractForm
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * Initialize the form validator and executor.
     *
     * @param Validator $validator [optional]
     */
    public function __construct(Validator $validator = null)
    {
        $this->validator = $validator ?: new Validator();
    }

    /**
     * Define custom attribute aliases.
     *
     * @see https://github.com/rakit/validation#attribute-alias
     *
     * @return array Returns the `attribute` => `alias` list.
     */
    protected function getAliases(): array
    {
        return [];
    }

    /**
     * Define custom messages for validation.
     *
     * Variables that can be uses in custom messages:
     *  - `:attribute`: will replaced into attribute alias.
     *  - `:value`: will replaced into stringify value of attribute.
     *   For array and object will replaced to json.
     * And also there are several message variables depends on their rules.
     *
     * @see https://github.com/rakit/validation#custom-validation-message
     *
     * @return array Returns the `rule` => `message` list.
     */
    protected function getMessages(): array
    {
        return [];
    }

    /**
     * Define rules for validation.
     *
     * @see https://github.com/rakit/validation#available-rules
     *
     * @return array Returns the `attribute` => `rule` list.
     */
    abstract protected function getRules(): array;

    /**
     * Define translations for words like <i>and</i> and <i>or</i>.
     *
     * @see https://github.com/rakit/validation#translation
     *
     * @return array Returns the translation list in the form
     *     'english' => 'translation'.
     */
    protected function getTranslations(): array
    {
        return [];
    }

    /**
     * Define actions to be done on success.
     *
     * @param Validation $result The validation result.
     * @return mixed
     */
    abstract protected function onSuccess(Validation $result);

    /**
     * Define actions to be done on failure.
     *
     * @param Validation $result The validation result.
     * @return mixed
     */
    abstract protected function onFailure(Validation $result);

    /**
     * Execute the validation and corresponding actions.
     *
     * @param array $data The data to be validated.
     * @return mixed
     */
    final public function execute(array $data)
    {
        $validation = $this->validator->make($data, $this->getRules());
        $validation->setAliases($this->getAliases());
        $validation->setMessages($this->getMessages());
        $validation->setTranslations($this->getTranslations());
        $validation->validate();

        return $validation->passes() ?
            $this->onSuccess($validation) : $this->onFailure($validation);
    }
}