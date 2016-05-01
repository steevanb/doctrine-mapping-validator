<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

class ValidatorOneToMany
{
    /** @var array */
    protected $validators = [];

    /**
     * @param ValidatorOneToManyInterface $validator
     * @param string $class
     * @param string $property
     * @return $this
     */
    public function addValidator(ValidatorOneToManyInterface $validator, $class, $property)
    {
        if (array_key_exists($class, $this->validators) === false) {
            $this->validators[$class] = [];
        }
        $this->validators[$class][$property] = $validator;

        return $this;
    }

    /**
     * @param object $object
     * @param string $property
     * @return ValidatorOneToManyInterface
     */
    public function getValidator($object, $property)
    {
        $class = get_class($object);
        if (array_key_exists($class, $this->validators) && array_key_exists($property, $this->validators[$class])) {
            $validator = $this->validators[$class][$property];
        } else {
            $validator = new DefaultValidatorOneToMany();
        }

        return $validator
            ->setLeftObject($object)
            ->setLeftObjectProperty($property);
    }

    /**
     * @param object $object
     * @param string $property
     * @return $this
     */
    public function validate($object, $property)
    {
        $this
            ->getValidator($object, $property)
            ->validate();

        return $this;
    }
}
