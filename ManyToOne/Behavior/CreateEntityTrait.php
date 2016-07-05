<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;

trait CreateEntityTrait
{
    /**
     * @return EntityManagerInterface
     */
    abstract protected function getManager();

    /**
     * @return string
     */
    abstract protected function getInverseSideClassName();

    /**
     * @return string
     */
    abstract protected function getInverseSideProperty();

    /**
     * @return object
     */
    abstract protected function getInverseSideEntity();

    /**
     * @return string
     */
    abstract protected function getInverseSideGetter();

    /**
     * @return string
     */
    abstract protected function getOwningSideClassName();

    /**
     * @return Report
     */
    abstract protected function getReport();

    /**
     * @return ValidationReport
     */
    abstract protected function getValidationReport();

    /**
     * @return object
     */
    protected function createOwningSideEntity()
    {
        $className = $this->getOwningSideClassName();
        $entity = new $className();
        $this->defineRandomData($entity);

        return $entity;
    }

    /**
     * @param string $validationName
     * @return object
     */
    protected function createInverseSideEntity($validationName)
    {
        $className = $this->getInverseSideClassName();
        $entity = new $className();
        $this->defineRandomData($entity);

        $getterReturn = call_user_func([ $entity, $this->getInverseSideGetter() ]);
        if ($getterReturn instanceof Collection === false) {
            $this->throwInverseSideDefaultGetterMustReturnCollection();
        } else {
            $this->addLInverseSideGetterDefaultValueValidation($validationName);
        }

        $this->getManager()->persist($entity);

        return $entity;
    }

    /**
     * @param object $entity
     * @return $this
     */
    protected function defineRandomData($entity)
    {
        $classMetadata = $this->getManager()->getClassMetadata(get_class($entity));
        $identifiers = $classMetadata->getIdentifier();
        foreach ($classMetadata->fieldMappings as $fieldMapping) {
            if (
                in_array($fieldMapping['columnName'], $identifiers) === false
                && (
                    array_key_exists('nullable', $fieldMapping) === false
                    || $fieldMapping['nullable'] === false
                )
            ) {
                $fieldValue = null;
                switch ($fieldMapping['type']) {
                    case 'string' :
                        $fieldValue = uniqid();
                        break;
                    case 'smallint':
                    case 'integer':
                    case 'bigint':
                        $fieldValue = rand(1, 1998);
                        break;
                    case 'date':
                    case 'datetime':
                        $fieldValue = new \DateTime();
                        break;
                }
                if ($fieldValue !== null) {
                    $entity->{'set' . $fieldMapping['columnName']}($fieldValue);
                }
            }
        }

        return $this;
    }

    /**
     * @param string $validationName
     * @return $this
     */
    protected function validateInverseSidePropertyDefaultValue($validationName)
    {
        $collection = call_user_func([ $this->getInverseSideEntity(), $this->getInverseSideGetter() ]);
        if ($collection instanceof Collection === false) {
            $this->throwInverseSideDefaultGetterMustReturnCollection();
        }

        $this->addLInverseSideGetterDefaultValueValidation($validationName);

        return $this;
    }

    /**
     * @throws ReportException
     */
    protected function throwInverseSideDefaultGetterMustReturnCollection()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideGetter() . '()';
        $message .= ' must return an instance of ' . Collection::class;
        $errorReport = new ErrorReport($message);

        $helpCollection = 'You should call $this->$' . $this->getInverseSideProperty();
        $helpCollection .= ' = new ' . ArrayCollection::class . '() in ';
        $helpCollection .= $this->getInverseSideClassName() . '::__construct().';
        $errorReport->addHelp($helpCollection);

        $helpReturn = $this->getInverseSideClassName() . '::' . $this->getInverseSideGetter() . '() should return ';
        $helpReturn .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . '.';
        $errorReport->addHelp($helpReturn);

        $errorReport->addMethodCode($this->getInverseSideEntity(), '__construct');
        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param string $validationName
     * @return $this
     */
    protected function addLInverseSideGetterDefaultValueValidation($validationName)
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideGetter() . '() ';
        $message .= 'return an instance of ' . Collection::class . '.';
        $this->getValidationReport()->addValidation($validationName, $message);

        return $this;
    }
}
