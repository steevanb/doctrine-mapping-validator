<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;

abstract class AbstractValidatorOneToMany implements ValidatorOneToManyInterface
{
    /** @var EntityManagerInterface */
    private $manager;

    /** @var string */
    private $leftEntityClassName;

    /** @var string */
    private $leftEntityProperty;

    /** @var string */
    private $rightEntityClassName;

    /** @var string */
    private $rightEntityProperty;

    /** @var Report */
    private $report;

    /** @var ValidationReport */
    private $validationReport;

    /**
     * @return $this
     */
    abstract protected function doValidate();

    /**
     * @param EntityManagerInterface $manager
     * @param string $className
     * @param string $property
     * @return Report
     */
    public function validate(EntityManagerInterface $manager, $className, $property)
    {
        $this->manager = $manager;

        $this->leftEntityClassName = $className;
        $this->leftEntityProperty = $property;

        $this->rightEntityClassName = $manager
            ->getClassMetadata($className)
            ->getAssociationMappings()[$property]['targetEntity'];
        $this->rightEntityProperty = $manager
            ->getClassMetadata($className)
            ->getAssociationMappedByTargetField($property);

        $this->report = new Report();
        $this->validationReport = new ValidationReport();

        $success = true;

        try {
            $this->doValidate();
        } catch (ReportException $e) {
            $success = false;
        } catch (\Exception $e) {
            $success = false;

            $errorReport = new ErrorReport($e->getMessage());
            $errorReport->addCodeLinePreview($e->getFile(), $e->getLine());
            $this->getReport()->addError($errorReport);
        }

        if ($success) {
            $this->getReport()->addValidation($this->getValidationReport());
        }

        return $this->getReport();
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getManager()
    {
        return $this->manager;
    }

    /**
     * @return string
     */
    protected function getLeftEntityClassName()
    {
        return $this->leftEntityClassName;
    }

    /**
     * @return string
     */
    protected function getLeftEntityProperty()
    {
        return $this->leftEntityProperty;
    }

    /**
     * @return string
     */
    protected function getRightEntityClassName()
    {
        return $this->rightEntityClassName;
    }

    /**
     * @return string
     */
    protected function getRightEntityPropperty()
    {
        return $this->rightEntityProperty;
    }

    /**
     * @return Report
     */
    protected function getReport()
    {
        return $this->report;
    }

    /**
     * @return ValidationReport
     */
    protected function getValidationReport()
    {
        return $this->validationReport;
    }
}
