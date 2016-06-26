<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait ValidateMethodsExistsTrait
{
    use PropertiesTrait;

    /**
     * @return $this
     */
    protected function validateMethodsExists()
    {
        $this
            ->validateLeftEntityMethodsExists()
            ->validateRightEntityMethodsExists();

        return $this;
    }

    /**
     * @return $this
     */
    protected function validateLeftEntityMethodsExists()
    {
        $leftEntityAdder = 'You must create this method in order to add ' . $this->rightEntityClass . ' in ';
        $leftEntityAdder .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';

        $leftEntitySetter = 'You must create this method in order to set all ' . $this->rightEntityClass . ' to ';
        $leftEntitySetter .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';

        $leftEntityGetter = 'You must create this method, in order to get ';
        $leftEntityGetter .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';

        $leftEntityRemover = 'You must create this method in order to remove ' . $this->rightEntityClass . ' in ';
        $leftEntityRemover .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';

        $leftEntityClearer = 'You must create this method in order to clear all ' . $this->rightEntityClass . ' in ';
        $leftEntityClearer .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';

        $methods = [
            [ 'method' => $this->leftEntityAdder, 'message' => $leftEntityAdder ],
            [ 'method' => $this->leftEntitySetter, 'message' => $leftEntitySetter ],
            [ 'method' => $this->leftEntityGetter, 'message' => $leftEntityGetter ],
            [ 'method' => $this->leftEntityRemover, 'message' => $leftEntityRemover ],
            [ 'method' => $this->leftEntityClearer, 'message' => $leftEntityClearer ],
        ];
        $this->assertEntityMethodsExists($this->leftEntity, $methods);

        return $this;
    }

    /**
     * @return $this
     */
    protected function validateRightEntityMethodsExists()
    {
        $rightEntityIdGetter = 'You must create this method in order to get ';
        $rightEntityIdGetter .= $this->rightEntityClass . '::$id';

        $rightEntitySetter = 'You must create this method in order to set ' . $this->leftEntityClass . ' to ';
        $rightEntitySetter .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';

        $rightEntityGetter = 'You must create this method in order to get ';
        $rightEntityGetter .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';

        $methods = [
            [ 'method' => $this->rightEntityIdGetter, 'message' => $rightEntityIdGetter ],
            [ 'method' => $this->rightEntitySetter, 'message' => $rightEntitySetter ],
            [ 'method' => $this->rightEntityGetter, 'message' => $rightEntityGetter ]
        ];
        $this->assertEntityMethodsExists($this->rightEntity, $methods);

        return $this;
    }

    /**
     * @param object $entity
     * @param array $methods
     * @return $this
     */
    protected function assertEntityMethodsExists($entity, $methods)
    {
        $methodsExists = [];
        foreach ($methods as $method) {
            $this->assertMethodExists($entity, $method['method'], $method['message']);
            $methodsExists[] = $method['method'] . '()';
            $this->passedReport->addMethodCode($entity, $method['method']);
        }

        $this->addPassedRequiredMethodsExistsTest($entity, $methodsExists);

        return $this;
    }

    /**
     * @param object $entity
     * @param string $method
     * @param string|null $error
     * @param string|null $help
     * @return $this
     * @throws ReportException
     */
    protected function assertMethodExists($entity, $method, $error = null, $help = null)
    {
        if (method_exists($entity, $method) === false) {
            $this->throwMethodDoesntExists($entity, $method, $error, $help);
        }

        return $this;
    }

    /**
     * @param object $entity
     * @param string $method
     * @param string|null $error
     * @param string|null $help
     * @throws ReportException
     */
    protected function throwMethodDoesntExists($entity, $method, $error, $help)
    {
        $class = get_class($entity);

        $message = $class . '::' . $method . '() does not exists.';
        $errorReport = new ErrorReport($message);
        if ($error !== null) {
            $errorReport->addError($error);
        }
        if ($help !== null) {
            $errorReport->addHelp($help);
        }

        throw new ReportException($this->report, $errorReport);
    }

    /**
     * @param object $entity
     * @param array $methods
     * @return $this
     */
    protected function addPassedRequiredMethodsExistsTest($entity, array $methods)
    {
        $message = 'All required methods exists into ' . get_class($entity);
        $message .= ' (' . implode(', ', $methods) . ').';
        $this->passedReport->addTest($this->initializationTestName, $message);

        return $this;
    }
}
