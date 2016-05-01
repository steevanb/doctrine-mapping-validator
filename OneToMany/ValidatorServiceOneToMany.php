<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\Report;

class ValidatorServiceOneToMany
{
    /** @var array */
    protected $validators = [];

    /**
     * @param EntityManagerInterface $manager
     * @param string $class
     * @param string $property
     * @param ValidatorOneToManyInterface $validator
     * @return $this
     */
    public function addValidator(
        EntityManagerInterface $manager,
        $class,
        $property,
        ValidatorOneToManyInterface $validator
    ) {
        $managerHash = spl_object_hash($manager);
        if (array_key_exists($managerHash, $this->validators) === false) {
            $this->validators[$managerHash] = [];
        }

        if (array_key_exists($class, $this->validators[$managerHash]) === false) {
            $this->validators[$managerHash][$class] = [];
        }

        $this->validators[$managerHash][$class][$property] = $validator;

        return $this;
    }

    /**
     * @param EntityManagerInterface $manager
     * @param object $object
     * @param string $property
     * @return ValidatorOneToManyInterface
     */
    public function getValidator(EntityManagerInterface $manager, $object, $property)
    {
        $managerHash = spl_object_hash($manager);
        $class = get_class($object);

        if (
            array_key_exists($managerHash, $this->validators)
            && array_key_exists($class, $this->validators[$managerHash])
            && array_key_exists($property, $this->validators[$managerHash][$class])
        ) {
            $validator = $this->validators[$managerHash][$class][$property];
        } else {
            $validator = new DefaultValidatorOneToMany();
        }

        return $validator
            ->setManager($manager)
            ->setLeftObject($object)
            ->setLeftObjectProperty($property)
            ->setReport(new Report());
    }

    /**
     * @param EntityManagerInterface $manager
     * @param object $object
     * @param string $property
     * @return Report
     */
    public function validate(EntityManagerInterface $manager, $object, $property)
    {
        return $this->getValidator($manager, $object, $property)->validate();
    }
}
