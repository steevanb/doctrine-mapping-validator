<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\Report;

interface ValidatorOneToManyInterface
{
    /**
     * @param EntityManagerInterface $manager
     * @return $this
     */
    public function setManager(EntityManagerInterface $manager);

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
     * @param Report $report
     * @return $this
     */
    public function setReport(Report $report);

    /**
     * @return $this
     */
    public function validate();
}
