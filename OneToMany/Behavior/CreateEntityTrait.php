<?php

namespace steevanb\DoctrineMappingValidator\OneToMany\Behavior;

use Doctrine\ORM\EntityManagerInterface;

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
    abstract protected function getRightEntityClassName();

    /**
     * @return object
     */
    protected function createLeftEntity()
    {
        $className = $this->getLeftEntityClassName();
        $entity = new $className();
        $this->defineRandomData($entity);

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
}
