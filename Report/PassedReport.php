<?php

namespace steevanb\DoctrineMappingValidator\Report;

class PassedReport
{
    use AddCodeTrait;

    /** @var string */
    protected $message;

    /** @var array */
    protected $tests = [];

    /**
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param $name
     * @param $message
     * @return $this
     */
    public function addTest($name, $message)
    {
        if (array_key_exists($name, $this->tests) === false) {
            $this->tests[$name] = [];
        }
        $this->tests[$name][] = $message;

        return $this;
    }

    /**
     * @return array
     */
    public function getTests()
    {
        return $this->tests;
    }
}
