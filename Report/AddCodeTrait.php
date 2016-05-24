<?php

namespace steevanb\DoctrineMappingValidator\Report;

trait AddCodeTrait
{
    /** @var array */
    protected $codes = [];

    /**
     * @param string $file
     * @param int $startLine
     * @param array $lines
     * @param int|null $highlight
     * @return $this
     */
    public function addCode($file, $startLine, $line, array $lines, $highlight = null)
    {
        $indexedLines = [];
        $lineIndex = 1;
        foreach ($lines as $codeLine) {
            $indexedLines[$startLine + $lineIndex] = rtrim($codeLine);
            $lineIndex++;
        }
        $this->codes[] = [
            'file' => $file,
            'line' => $line,
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
        if (method_exists($object, $method) === false) {
            return $this;
        }

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
                $startLine + 1,
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
            $line,
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
