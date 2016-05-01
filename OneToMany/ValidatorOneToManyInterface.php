<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

interface ValidatorOneToManyInterface
{
    /**
     * @param object $object
     * @return $this
     */
    public function setLeftObject($object);

    /**
     * @param string $property
     * @return $this
     */
    public function setLeftObjectProperty($property);

    /**
     * @return $this
     */
    public function validate();
}
