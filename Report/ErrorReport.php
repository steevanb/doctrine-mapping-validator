<?php

namespace steevanb\DoctrineMappingValidator\Report;

class ErrorReport
{
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

    /**
     * @param string $file
     * @param int $line
     * @param string $code
     * @return $this
     */
    public function addCode($file, $line, $code)
    {
        $this->codes[] = [
            'file' => $file,
            'line' => $line,
            'code' => $code
        ];

        return $this;
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
        // if method is in trait, getStartLine() return line of "last" used trait (not the right one)
        $findMethodDeclaration = 2;
        $methodDeclarationFound = false;
        while ($findMethodDeclaration > 0) {
            $line = array_slice($classLines, $startLine, 1)[0];
            if (strpos($line, $method) === false) {
                $startLine--;
                $coutLines++;
                $findMethodDeclaration--;
            } else {
                $methodDeclarationFound = true;
                break;
            }
        }
        if ($methodDeclarationFound) {
            $this->addCode(
                $reflection->getFileName(),
                $startLine,
                implode(null, array_slice($classLines, $startLine, $coutLines))
            );
            $this->codes[] = [
                'file' => $reflection->getFileName(),
                'line' => $startLine,
                'code' => implode(null, array_slice($classLines, $startLine, $coutLines))
            ];
        }

        return $this;
    }

    /**
     * @param string $file
     * @param int $line
     * @return $this
     */
    public function addCodeLinePreview($file, $line)
    {
        $lines = file($file);
        $startLine = max(0, $line - 4);
        $endLine = min(count($lines), $line + 4);

        $this->addCode(
            $file,
            $line,
            implode(null, array_slice($lines, $startLine, $endLine - $startLine))
        );

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
