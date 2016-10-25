<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;

trait CreateEntityTrait
{
    /** @return EntityManagerInterface */
    abstract protected function getManager();

    /** @return string */
    abstract protected function getInverseSideClassName();

    /** @return string */
    abstract protected function getInverseSideProperty();

    /** @return object */
    abstract protected function getInverseSideEntity();

    /** @return string */
    abstract protected function getInverseSideGetter();

    /** @return string */
    abstract protected function getOwningSideClassName();

    /** @return bool */
    abstract protected function isBidirectionnal();

    /** @return Report */
    abstract protected function getReport();

    /** @return ValidationReport */
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
     * @return object
     */
    protected function createInverseSideEntity()
    {
        $className = $this->getInverseSideClassName();
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
                $entity->{$this->getFieldSetter($fieldMapping)}($this->getFieldValue($fieldMapping));
            }
        }

        return $this;
    }

    /**
     * @param array $fieldMapping
     * @return mixed
     */
    protected function getFieldValue(array $fieldMapping)
    {
        switch ($fieldMapping['type']) {
            case 'string' :
                $return = uniqid();
                break;
            case 'smallint':
            case 'integer':
            case 'bigint':
                $return = rand(1, 1998);
                break;
            case 'date':
            case 'datetime':
                $return = new \DateTime();
                break;
            case 'boolean':
                $return = true;
                break;
            default:
                $return = null;
        }

        return $return;
    }

    /**
     * @param array $fieldMapping
     * @return string
     */
    protected function getFieldSetter(array $fieldMapping)
    {
        return 'set' . str_replace('_', null, $fieldMapping['columnName']);
    }
}
