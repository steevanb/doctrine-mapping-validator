<?php

namespace steevanb\DoctrineMappingValidator\Report;

trait AddCodeTrait
{
    /** @var array */
    protected $codes = [];

    /**
     * @param string $file
     * @param array $lines
     * @return $this
     */
    public function addCode($file, array $lines)
    {
        $this->codes[] = [
            'file' => $file,
            'line' => 1,
            'lines' => $lines,
            'highlight' => null
        ];

        return $this;
    }

    /**
     * @param string $file
     * @param int $startLine
     * @param int $line
     * @param array $lines
     * @param int|null $highlight
     * @return $this
     */
    public function addCodeLinesFromFile($file, $startLine, $line, array $lines, $highlight = null)
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
     * @param string $className
     * @param string $method
     * @return $this
     */
    public function addMethodCode($className, $method)
    {
        static $addedMethods = [];
        if (isset($addedMethods[$className][$method])) {
            return $this;
        }

        $reflectionMethod = (new \ReflectionClass($className))->getMethod($method);
        $classLines = file($reflectionMethod->getFileName());

        $startLine = $reflectionMethod->getStartLine();
        $coutLines = $reflectionMethod->getEndLine() - $startLine;

        // $startLine is the line of {
        // try to find function foo() line
        $findMethodDeclaration = 2;
        while ($findMethodDeclaration > 0) {
            $line = array_slice($classLines, $startLine, 1)[0];
            if (strpos($line, $method) === false) {
                $startLine--;
                $coutLines++;
                $findMethodDeclaration--;
            } else {
                break;
            }
        }

        $this->addCodeLinesFromFile(
            $reflectionMethod->getFileName(),
            $startLine,
            $startLine + 1,
            array_slice($classLines, $startLine, $coutLines)
        );

        if (array_key_exists($className, $addedMethods) === false) {
            $addedMethods[$className] = [];
        }
        $addedMethods[$className][$method] = true;

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

        $this->addCodeLinesFromFile(
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
