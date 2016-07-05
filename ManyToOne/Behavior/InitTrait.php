<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;
use steevanb\DoctrineMappingValidator\Report\Report;

trait InitTrait
{
    use PropertiesTrait;

    /**
     * @param EntityManagerInterface $manager
     * @param string $leftEntityClassName
     * @param string $property
     * @return $this;
     */
    protected function init(EntityManagerInterface $manager, $leftEntityClassName, $property)
    {
        $this->report = new Report();
        $this->manager = $manager;
        $this->managerClass = get_class($manager);

        $this->leftEntityClass = $leftEntityClassName;
        $this->leftEntity = $this->createLeftEntity();
        $this->manager->persist($this->leftEntity);
        $this->leftEntityProperty = $property;
        $this->leftEntityAdder = 'add' . ucfirst(substr($this->leftEntityProperty, 0, -1));
        $this->leftEntityAdderTestName = $this->leftEntityClass . '::' . $this->leftEntityAdder . '()';
        $this->leftEntitySetter = 'set' . ucfirst($this->leftEntityProperty);
        $this->leftEntitySetterTestName = $this->leftEntityClass . '::' . $this->leftEntitySetter . '()';
        $this->leftEntityGetter = 'get' . ucfirst($this->leftEntityProperty);
        $this->leftEntityRemover = 'remove' . ucfirst(substr($this->leftEntityProperty, 0, -1));
        $this->leftEntityRemoverTestName = $this->leftEntityClass . '::' . $this->leftEntityRemover . '()';
        $this->leftEntityClearer = 'clear' . ucfirst($this->leftEntityProperty);
        $this->leftEntityClearerTestName = $this->leftEntityClass . '::' . $this->leftEntityClearer . '()';

        $this->rightEntityClass = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->getAssociationMappings()[$this->leftEntityProperty]['targetEntity'];
        $this->rightEntity = $this->createRightEntity();
        $this->rightEntity2 = $this->createRightEntity();
        $this->rightEntityProperty = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->getAssociationMappedByTargetField($this->leftEntityProperty);
        $this->rightEntitySetter = 'set' . ucfirst($this->rightEntityProperty);
        $this->rightEntityGetter = 'get' . ucfirst($this->rightEntityProperty);
        $this->rightEntityIdGetter = 'getId';

        $message = $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' : ';
        $message .= 'manyToOne with ' . $this->rightEntityClass;
        $this->validationReport = new ValidationReport($message);
    }
}
