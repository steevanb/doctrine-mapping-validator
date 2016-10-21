<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;

abstract class AbstractValidatorManyToOne implements ValidatorManyToOneInterface
{
    /** @var EntityManagerInterface */
    private $manager;

    /** @var string */
    private $inverseSideClassName;

    /** @var object */
    private $inverseSideEntity;

    /** @var string */
    private $inverseSideProperty;

    /** @var string */
    private $inverseSideGetter;

    /** @var string */
    private $owningSideClassName;

    /** @var object */
    private $owningSideEntity;

    /** @var string */
    private $owningSideProperty;

    /** @var string */
    private $owningSideSetter;

    /** @var string */
    private $owningSideGetter;

    /** @var string */
    private $owningSideIdGetter = 'getId';

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
     * @param string $owningSideClassName
     * @param string $property
     * @return Report
     */
    public function validate(EntityManagerInterface $manager, $owningSideClassName, $property)
    {
        $this->manager = $manager;

        $this->owningSideClassName = $owningSideClassName;
        $this->owningSideProperty = $property;

        $this->report = new Report();
        $this->validationReport = new ValidationReport();

        $success = true;
        try {
            $associationMapping = $manager
                ->getClassMetadata($owningSideClassName)
                ->getAssociationMappings()[$property];
            if ($associationMapping['type'] !== ClassMetadataInfo::MANY_TO_ONE) {
                throw new ReportException($owningSideClassName . '::' . $property . ' is not a manyToOne.');
            }

            $this->inverseSideClassName = $associationMapping['targetEntity'];
            foreach ($manager->getClassMetadata($this->inverseSideClassName)->getAssociationMappings() as $mapping) {
                if ($mapping['targetEntity'] === $owningSideClassName) {
                    $this->inverseSideProperty = $mapping['fieldName'];
                    break;
                }
            }
            if ($this->inverseSideProperty === null) {
                throw new \Exception('Can\'t found inverse side property on "' . $this->inverseSideClassName . '".');
            }
            $this->defineMethodNames();

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
     * @return $this
     */
    protected function defineMethodNames()
    {
        $this->owningSideGetter = 'get' . ucfirst($this->getOwningSideProperty());
        $this->owningSideSetter = 'set' . ucfirst($this->getOwningSideProperty());

        $this->inverseSideGetter = 'get' . ucfirst($this->getInverseSideProperty());

        return $this;
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
    protected function getInverseSideClassName()
    {
        return $this->inverseSideClassName;
    }

    /**
     * @param object $entity
     * @return $this
     */
    protected function setInverseSideEntity($entity)
    {
        $this->inverseSideEntity = $entity;

        return $this;
    }

    /**
     * @return object
     */
    protected function getInverseSideEntity()
    {
        return $this->inverseSideEntity;
    }

    /**
     * @return string
     */
    protected function getInverseSideProperty()
    {
        return $this->inverseSideProperty;
    }

    /**
     * @return string
     */
    protected function getInverseSideGetter()
    {
        return $this->inverseSideGetter;
    }

    /**
     * @return string
     */
    protected function getOwningSideClassName()
    {
        return $this->owningSideClassName;
    }

    /**
     * @param object $entity
     * @return $this
     */
    protected function setOwningSideEntity($entity)
    {
        $this->owningSideEntity = $entity;

        return $this;
    }

    /**
     * @return object
     */
    protected function getOwningSideEntity()
    {
        return $this->owningSideEntity;
    }

    /**
     * @return string
     */
    protected function getOwningSideProperty()
    {
        return $this->owningSideProperty;
    }

    /**
     * @return string
     */
    protected function getOwningSideSetter()
    {
        return $this->owningSideSetter;
    }

    /**
     * @return string
     */
    protected function getOwningSideGetter()
    {
        return $this->owningSideGetter;
    }

    /**
     * @return string
     */
    protected function getOwningSideIdGetter()
    {
        return $this->owningSideIdGetter;
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
