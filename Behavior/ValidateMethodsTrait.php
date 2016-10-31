<?php

namespace steevanb\DoctrineMappingValidator\Behavior;

use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;

trait ValidateMethodsTrait
{
    /** @return Report */
    abstract protected function getReport();

    /** @return ValidationReport */
    abstract protected function getValidationReport();

    /**
     * @param object $entity
     * @param array $methods
     * @param string $validationName
     * @return $this
     */
    protected function validateMethods($entity, array $methods, $validationName)
    {
        $methodsExists = [];
        foreach ($methods as $method) {
            if (method_exists($entity, $method['method']) === false) {
                $this->throwMethodDoesNotExists($entity, $method['method'], $method['error']);
            }
            $this->validateMethodParameters(get_class($entity), $method['method'], $method['parameters']);
            $methodsExists[] = $method['method'] . '()';
            $this->getValidationReport()->addMethodCode(get_class($entity), $method['method']);
        }

        $this->addMethodsValidation($entity, $methodsExists, $validationName);

        return $this;
    }

    /**
     * @param string $className
     * @param string $method
     * @param array $parameters
     * @return $this
     * @throws ReportException
     */
    protected function validateMethodParameters($className, $method, array $parameters)
    {
        $reportError = false;

        $reflection = new \ReflectionClass($className);
        $countParameters = 0;
        $countRequiredParameters = 0;
        foreach ($parameters as $allowedTypes) {
            $countParameters++;
            if (count($allowedTypes) !== 2) {
                $countRequiredParameters++;
            }
        }
        if ($reflection->getMethod($method)->getNumberOfRequiredParameters() !== $countRequiredParameters) {
            $reportError = true;
        } elseif ($reflection->getMethod($method)->getNumberOfParameters() !== $countParameters) {
        } else {
            $methodParameters = $reflection->getMethod($method)->getParameters();
            $parameterIndex = 0;
            $isPhp7 = version_compare(phpversion(), '7.0.0') >= 0;
            foreach ($parameters as $allowedTypes) {
                // we can test parameter type since PHP7, before, we can just test default value
                if ($isPhp7) {
                    $type = (string) $methodParameters[$parameterIndex]->getType();
                    if (in_array($type, $allowedTypes) === false) {
                        $reportError = true;
                        break;
                    }
                }

                $parameterIndex++;
            }
        }

        if ($reportError) {
            $this->throwMethodSignatureIsWrong($className, $method, $parameters);
        }

        return $this;
    }

    /**
     * @param object $entity
     * @param string $method
     * @param string|null $error
     * @throws ReportException
     */
    protected function throwMethodDoesNotExists($entity, $method, $error)
    {
        $class = get_class($entity);

        $message = $class . '::' . $method . '() does not exists.';
        $errorReport = new ErrorReport($message);
        if ($error !== null) {
            $errorReport->addError($error);
        }

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param string $className
     * @param string $method
     * @param array $parameters
     * @throws ReportException
     */
    protected function throwMethodSignatureIsWrong($className, $method, array $parameters)
    {
        $message = $className . '::' . $method . '() signature is wrong.';
        $errorReport = new ErrorReport($message);

        $help = $className . '::' . $method . '() must have at least this ';
        $help .= (count($parameters) === 1) ? 'parameter: ' : 'parameters: ';
        $helpParameters = [];
        foreach ($parameters as $name => $types) {
            switch (count($types)) {
                case 0:
                    $helpParameters[] = '$' . $name;
                    break;
                case 1:
                    $helpParameters[] = $types[0] . ' $' . $name;
                    break;
                case 2:
                    $helpParameters[] = $types[0] . ' $' . $name . ' = ' . $types[1];
                    break;
            }
        }
        $help .= implode(', ', $helpParameters) . '.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($className, $method);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param object $entity
     * @param array $methods
     * @param string $validationName
     * @return $this
     */
    protected function addMethodsValidation($entity, array $methods, $validationName)
    {
        $message = 'All required methods exists and have correct signature into ' . get_class($entity);
        $message .= ' (' . implode(', ', $methods) . ').';
        $this->getValidationReport()->addValidation($validationName, $message);

        return $this;
    }
}
