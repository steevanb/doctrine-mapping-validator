<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\Report;

class ValidatorServiceManyToOne
{
    /** @var array */
    protected $validators = [];

    /**
     * @param EntityManagerInterface $manager
     * @param string $class
     * @param string $property
     * @param ValidatorManyToOneInterface $validator
     * @return $this
     */
    public function addValidator(
        EntityManagerInterface $manager,
        $class,
        $property,
        ValidatorManyToOneInterface $validator
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
     * @param string $owningSideClassName
     * @param string $property
     * @return ValidatorManyToOneInterface
     * @throws \Exception
     */
    public function getValidator(EntityManagerInterface $manager, $owningSideClassName, $property)
    {
        $managerHash = spl_object_hash($manager);

        if (
            array_key_exists($managerHash, $this->validators)
            && array_key_exists($owningSideClassName, $this->validators[$managerHash])
            && array_key_exists($property, $this->validators[$managerHash][$owningSideClassName])
        ) {
            $validator = $this->validators[$managerHash][$owningSideClassName][$property];
        } else {
            $validator = new DefaultValidatorManyToOne();
        }

        return $validator;
    }

    /**
     * @param EntityManagerInterface $manager
     * @param string $owningSideClassName
     * @param string $property
     * @param ValidatorManyToOneInterface|null $validator
     * @return Report
     */
    public function validate(
        EntityManagerInterface $manager,
        $owningSideClassName,
        $property,
        ValidatorManyToOneInterface $validator = null
    ) {
        $validator = ($validator instanceof ValidatorManyToOneInterface)
            ? $validator
            : $this->getValidator($manager, $owningSideClassName, $property);

        return $validator->validate($manager, $owningSideClassName, $property);
    }
}
