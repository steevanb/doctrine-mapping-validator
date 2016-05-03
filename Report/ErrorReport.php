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
     * @param int $startLine
     * @param array $lines
     * @param int|null $highlight
     * @return $this
     */
    public function addCode($file, $startLine, array $lines, $highlight = null)
    {
        $indexedLines = [];
        $lineIndex = 1;
        foreach ($lines as $line) {
            $indexedLines[$startLine + $lineIndex] = rtrim($line);
            $lineIndex++;
        }
        $this->codes[] = [
            'file' => $file,
            'startLine' => $startLine,
            'lines' => $indexedLines,
            'highlight' => $highlight
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
                array_slice($classLines, $startLine, $coutLines)
            );
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
        $startLine = max(0, $line - 5);
        $endLine = min(count($lines), $line + 4);

        $this->addCode(
            $file,
            $startLine,
            array_slice($lines, $startLine, $endLine - $startLine),
            $line
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
