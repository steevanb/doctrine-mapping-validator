<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\Report;

interface ValidatorOneToManyInterface
{
    /**
     * @param EntityManagerInterface $manager
     * @param string $leftEntityClassName
     * @param string $property
     * @return Report
     */
    public function validate(EntityManagerInterface $manager, $leftEntityClassName, $property);
}
