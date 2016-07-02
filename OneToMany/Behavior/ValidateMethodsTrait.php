<?php

namespace steevanb\DoctrineMappingValidator\OneToMany\Behavior;

use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\ReportException;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;

trait ValidateMethodsTrait
{
    /**
     * @return ValidationReport
     */
    abstract protected function getValidationReport();

//    /**
//     * @return $this
//     */
//    protected function validateLeftEntityMethodsExists()
//    {
//        $leftEntityAdder = 'You must create this method in order to add ' . $this->rightEntityClass . ' in ';
//        $leftEntityAdder .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';
//
//        $leftEntitySetter = 'You must create this method in order to set all ' . $this->rightEntityClass . ' to ';
//        $leftEntitySetter .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';
//
//        $leftEntityGetter = 'You must create this method, in order to get ';
//        $leftEntityGetter .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';
//
//        $leftEntityRemover = 'You must create this method in order to remove ' . $this->rightEntityClass . ' in ';
//        $leftEntityRemover .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';
//
//        $leftEntityClearer = 'You must create this method in order to clear all ' . $this->rightEntityClass . ' in ';
//        $leftEntityClearer .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';
//
//        $methods = [
//            [ 'method' => $this->leftEntityAdder, 'message' => $leftEntityAdder ],
//            [ 'method' => $this->leftEntitySetter, 'message' => $leftEntitySetter ],
//            [ 'method' => $this->leftEntityGetter, 'message' => $leftEntityGetter ],
//            [ 'method' => $this->leftEntityRemover, 'message' => $leftEntityRemover ],
//            [ 'method' => $this->leftEntityClearer, 'message' => $leftEntityClearer ],
//        ];
//        $this->assertEntityMethodsExists($this->leftEntity, $methods);
//
//        return $this;
//    }
//
//    /**
//     * @return $this
//     */
//    protected function validateRightEntityMethodsExists()
//    {
//        $rightEntityIdGetter = 'You must create this method in order to get ';
//        $rightEntityIdGetter .= $this->rightEntityClass . '::$id';
//
//        $rightEntitySetter = 'You must create this method in order to set ' . $this->leftEntityClass . ' to ';
//        $rightEntitySetter .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';
//
//        $rightEntityGetter = 'You must create this method in order to get ';
//        $rightEntityGetter .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';
//
//        $methods = [
//            [ 'method' => $this->rightEntityIdGetter, 'message' => $rightEntityIdGetter ],
//            [ 'method' => $this->rightEntitySetter, 'message' => $rightEntitySetter ],
//            [ 'method' => $this->rightEntityGetter, 'message' => $rightEntityGetter ]
//        ];
//        $this->assertEntityMethodsExists($this->rightEntity, $methods);
//
//        return $this;
//    }

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
            if (method_exists($entity, $method[0]) === false) {
                $this->throwMethodDoesntExists($entity, $method[0], $method[1]);
            }
            $this->validateMethodParameters(get_class($entity), $method[0], $method[2]);
            $methodsExists[] = $method[0] . '()';
            $this->getValidationReport()->addMethodCode($entity, $method[0]);
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
        foreach ($parameters as $parameter) {
            $countParameters++;
            if (count($parameter) !== 2) {
                $countRequiredParameters++;
            }
        }
        if ($reflection->getMethod($method)->getNumberOfRequiredParameters() !== $countRequiredParameters) {
            $reportError = true;
        } elseif ($reflection->getMethod($method)->getNumberOfParameters() !== $countParameters) {
        } else {
            $methodParameters = $reflection->getMethod($method)->getParameters();
            $parameterIndex = 0;
            foreach ($parameters as $types) {
                $type = (string)$methodParameters[$parameterIndex]->getType();
                if (in_array($type, $types) === false) {
                    $reportError = true;
                    break;
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
    protected function throwMethodDoesntExists($entity, $method, $error)
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
