<?php

namespace steevanb\DoctrineMappingValidator\Report;

class ErrorReport
{
    use AddCodeTrait;

    /** @var string */
    protected $message;

    /** @var string[] */
    protected $errors = [];

    /** @var string[] */
    protected $helps = [];

    /** @var string[] */
    protected $files = [];

    /** @var string[] */
    protected $links = [];

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
     * @param string $error
     * @return $this
     */
    public function addError($error)
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $help
     * @return $this
     */
    public function addHelp($help)
    {
        $this->helps[] = $help;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getHelps()
    {
        return $this->helps;
    }

    /**
     * @param string $file
     * @return $this
     */
    public function addFile($file)
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param string $link
     * @return $this
     */
    public function addLink($link)
    {
        $this->links[] = $link;

        return $this;
    }

    /**
     * @return array
     */
    public function getLinks()
    {
        return $this->links;
    }
}
