<?php

namespace steevanb\DoctrineMappingValidator\Report;

class ErrorReport
{
    /** @var string */
    protected $message;

    /** @var string[] */
    protected $extraMessages = [];

    /** @var string[] */
    protected $files = [];

    /** @var string[] */
    protected $links = [];

    /** @var array */
    protected $codes = [];

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
     * @param string $message
     * @return $this
     */
    public function addExtraMessage($message)
    {
        $this->extraMessages[] = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtraMessages()
    {
        return $this->extraMessages;
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

    /**
     * @param object $object
     * @param string $method
     * @return $this
     */
    public function addMethodCode($object, $method)
    {
        $reflection = new \ReflectionClass($object);
        $reflectionMethod = $reflection->getMethod($method);
        $classLines = file($reflection->getFileName());
        $startLine = $reflectionMethod->getStartLine();
        $coutLines = $reflectionMethod->getEndLine() - $startLine;
        if (trim($classLines[$startLine]) === '{') {
            $startLine--;
            $coutLines++;
        }

        $this->codes[] = [
            'file' => $reflection->getFileName(),
            'line' => $startLine,
            'code' => implode(null, array_slice($classLines, $startLine, $coutLines))
        ];

        return $this;
    }

    /**
     * @return array
     */
    public function getCodes()
    {
        return $this->codes;
    }
}
