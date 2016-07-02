<?php

namespace steevanb\DoctrineMappingValidator\Report;

class ValidationReport
{
    use AddCodeTrait;

    /** @var string */
    protected $message;

    /** @var array */
    protected $validations = [];

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $name
     * @param string $message
     * @return $this
     */
    public function addValidation($name, $message)
    {
        if (array_key_exists($name, $this->validations) === false) {
            $this->validations[$name] = [];
        }
        $this->validations[$name][] = $message;

        return $this;
    }

    /**
     * @return array
     */
    public function getValidations()
    {
        return $this->validations;
    }
}
