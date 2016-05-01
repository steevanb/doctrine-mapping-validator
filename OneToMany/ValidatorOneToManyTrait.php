<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\Report;

trait ValidatorOneToManyTrait
{
    /** @var EntityManagerInterface */
    protected $manager;

    /** @var object */
    protected $leftObject;

    /** @var string */
    protected $leftObjectProperty;

    /** @var Report */
    protected $report;

    /**
     * @param EntityManagerInterface $manager
     * @return $this
     */
    public function setManager(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param object $object
     * @return $this
     */
    public function setLeftObject($object)
    {
        $this->leftObject = $object;

        return $this;
    }

    /**
     * @return object
     */
    public function getLeftObject()
    {
        return $this->leftObject;
    }

    /**
     * @param string $property
     * @return $this
     */
    public function setLeftObjectProperty($property)
    {
        $this->leftObjectProperty = $property;

        return $this;
    }

    /**
     * @return string
     */
    public function getLeftObjectProperty()
    {
        return $this->leftObjectProperty;
    }

    /**
     * @param Report $report
     * @return $this
     */
    public function setReport(Report $report)
    {
        $this->report = $report;

        return $this;
    }

    /**
     * @return Report
     */
    public function getReport()
    {
        return $this->report;
    }
}
