<?php

namespace steevanb\DoctrineMappingValidator\Behavior;

trait GetMethodCodeTrait
{
    /**
     * @param string $className
     * @param string $method
     * @return $this
     */
    public function getMethodCode($className, $method)
    {
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

        $return = [];
        foreach (array_slice($classLines, $startLine, $coutLines) as $codeLine) {
            $return[] = rtrim($codeLine);
        }

        return implode("\n", $return);
    }
}
