<?php

namespace steevanb\DoctrineMappingValidator\OneToMany\Behavior;

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
    abstract protected function getLeftEntityClassName();

    /**
     * @return string
     */
    abstract protected function getLeftEntityProperty();

    /**
     * @return object
     */
    abstract protected function getLeftEntity();

    /**
     * @return string
     */
    abstract protected function getLeftEntityGetter();

    /**
     * @return string
     */
    abstract protected function getRightEntityClassName();

    /**
     * @return Report
     */
    abstract protected function getReport();

    /**
     * @return ValidationReport
     */
    abstract protected function getValidationReport();

    /**
     * @param string $validationName
     * @return object
     */
    protected function createLeftEntity($validationName)
    {
        $className = $this->getLeftEntityClassName();
        $entity = new $className();
        $this->defineRandomData($entity);

        $getterReturn = call_user_func([ $entity, $this->getLeftEntityGetter() ]);
        if ($getterReturn instanceof Collection === false) {
            $this->throwLeftEntityDefaultGetterMustReturnCollection();
        } else {
            $this->addLeftEntityGetterDefaultValueValidation($validationName);
        }

        $this->getManager()->persist($entity);

        return $entity;
    }

    /**
     * @return object
     */
    protected function createRightEntity()
    {
        $className = $this->getRightEntityClassName();
        $entity = new $className();
        $this->defineRandomData($entity);

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
    protected function validateLeftEntityPropertyDefaultValue($validationName)
    {
        $collection = call_user_func([ $this->getLeftEntity(), $this->getLeftEntityGetter() ]);
        if ($collection instanceof Collection === false) {
            $this->throwLeftEntityDefaultGetterMustReturnCollection();
        }

        $this->addLeftEntityGetterDefaultValueValidation($validationName);

        return $this;
    }

    /**
     * @throws ReportException
     */
    protected function throwLeftEntityDefaultGetterMustReturnCollection()
    {
        $message = $this->getLeftEntityClassName() . '::' . $this->getLeftEntityGetter() . '()';
        $message .= ' must return an instance of ' . Collection::class;
        $errorReport = new ErrorReport($message);

        $helpCollection = 'You should call $this->$' . $this->getLeftEntityProperty();
        $helpCollection .= ' = new ' . ArrayCollection::class . '() in ';
        $helpCollection .= $this->getLeftEntityClassName() . '::__construct().';
        $errorReport->addHelp($helpCollection);

        $helpReturn = $this->getLeftEntityClassName() . '::' . $this->getLeftEntityGetter() . '() should return ';
        $helpReturn .= $this->getLeftEntityClassName() . '::$' . $this->getLeftEntityProperty() . '.';
        $errorReport->addHelp($helpReturn);

        $errorReport->addMethodCode($this->getLeftEntity(), '__construct');
        $errorReport->addMethodCode($this->getLeftEntity(), $this->getLeftEntityGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param string $validationName
     * @return $this
     */
    protected function addLeftEntityGetterDefaultValueValidation($validationName)
    {
        $message = $this->getLeftEntityClassName() . '::' . $this->getLeftEntityGetter() . '() ';
        $message .= 'return an instance of ' . Collection::class . '.';
        $this->getValidationReport()->addValidation($validationName, $message);

        return $this;
    }
}
