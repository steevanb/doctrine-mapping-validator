<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\Report;

interface ValidatorManyToOneInterface
{
    /**
     * @param EntityManagerInterface $manager
     * @param string $owningSideClassName
     * @param string $property
     * @return Report
     */
    public function validate(EntityManagerInterface $manager, $owningSideClassName, $property);
}
