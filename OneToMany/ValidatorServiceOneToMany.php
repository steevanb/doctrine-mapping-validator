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
     * @param object $entity
     * @param string $property
     * @return ValidatorOneToManyInterface
     * @throws \Exception
     */
    public function getValidator(EntityManagerInterface $manager, $entity, $property)
    {
        $managerHash = spl_object_hash($manager);
        if (is_object($entity) === false) {
            throw new \Exception('Excepted $entity to be an entity, ' . gettype($entity) . ' given.');
        }
        $class = get_class($entity);

        if (
            array_key_exists($managerHash, $this->validators)
            && array_key_exists($class, $this->validators[$managerHash])
            && array_key_exists($property, $this->validators[$managerHash][$class])
        ) {
            $validator = $this->validators[$managerHash][$class][$property];
        } else {
            $validator = new DefaultValidatorOneToMany();
        }

        return $validator;
    }

    /**
     * @param EntityManagerInterface $manager
     * @param object $entity
     * @param string $property
     * @return Report
     */
    public function validate(EntityManagerInterface $manager, $entity, $property)
    {
        return $this
            ->getValidator($manager, $entity, $property)
            ->validate($manager, $entity, $property);
    }
}
