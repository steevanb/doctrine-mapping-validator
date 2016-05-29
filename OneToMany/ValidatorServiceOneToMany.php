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
     * @param string $leftEntityClassName
     * @param string $property
     * @return ValidatorOneToManyInterface
     * @throws \Exception
     */
    public function getValidator(EntityManagerInterface $manager, $leftEntityClassName, $property)
    {
        $managerHash = spl_object_hash($manager);

        if (
            array_key_exists($managerHash, $this->validators)
            && array_key_exists($leftEntityClassName, $this->validators[$managerHash])
            && array_key_exists($property, $this->validators[$managerHash][$leftEntityClassName])
        ) {
            $validator = $this->validators[$managerHash][$leftEntityClassName][$property];
        } else {
            $validator = new DefaultValidatorOneToMany();
        }

        return $validator;
    }

    /**
     * @param EntityManagerInterface $manager
     * @param string $leftEntityClassName
     * @param string $property
     * @return Report
     */
    public function validate(EntityManagerInterface $manager, $leftEntityClassName, $property)
    {
        return $this
            ->getValidator($manager, $leftEntityClassName, $property)
            ->validate($manager, $leftEntityClassName, $property);
    }
}
