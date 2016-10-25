<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;

trait ValidateInverseSideAdderTrait
{
    /** @var string */
    protected $inverseSideAdderValidationName;

    /** @return array */
    abstract protected function getOwningSideGetterMethodValidation();

    /** @return string */
    abstract protected function getOwningSideClassName();

    /** @return object */
    abstract protected function createOwningSideEntity();

    /**
     * @param object $entity
     * @return $this
     */
    abstract protected function setOwningSideEntity($entity);

    /** @return object */
    abstract protected function getOwningSideEntity();

    /** @return string */
    abstract protected function getOwningSideProperty();

    /** @return string */
    abstract protected function getOwningSideSetter();

    /** @return array */
    abstract protected function getOwningSideSetterMethodValidation();

    /** @return string */
    abstract protected function getOwningSideGetter();

    /** @return string */
    abstract protected function getOwningSideIdGetter();

    /** @return array */
    abstract protected function getOwningSideIdGetterMethodValidation();

    /** @return object */
    abstract protected function createInverseSideEntity();

    /**
     * @param object $entity
     * @return $this
     */
    abstract protected function setInverseSideEntity($entity);

    /** @return string */
    abstract protected function getInverseSideProperty();

    /** @return string */
    abstract protected function getInverseSideClassName();

    /** @return string */
    abstract protected function getInverseSideAdder();

    /** @return array */
    abstract protected function getInverseSideAdderMethodValidation();

    /** @return string */
    abstract protected function getInverseSideGetter();

    /** @return array */
    abstract protected function getInverseSideGetterMethodValidation();

    /** @return object */
    abstract protected function getInverseSideEntity();

    /** @return EntityManagerInterface */
    abstract protected function getManager();

    /** @return bool */
    abstract protected function isBidirectionnal();

    /** @return Report */
    abstract protected function getReport();

    /** @return ValidationReport */
    abstract protected function getValidationReport();

    /**
     * @param object $entity
     * @param array $methods
     * @param string $validationName
     * @return $this
     */
    abstract protected function validateMethods($entity, array $methods, $validationName);

    /**
     * @return $this
     */
    protected function validateInverseSideAdder()
    {
        $this
            ->inverseSideAdderInit()
            ->inverseSideAdderValidateOwningSideMethods()
            ->inverseSideAdderValidateInverseSideMethods()
            ->inverseSideAdderValidateAdder()
            ->inverseSideAdderValidateFlush()
            ->inverseSideAdderValidateRefresh();

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideAdderInit()
    {
        $this->inverseSideAdderValidationName =
            $this->getInverseSideClassName() . '::' . $this->getInverseSideAdder() . '()';
        $this->setInverseSideEntity($this->createInverseSideEntity());

        $this->setOwningSideEntity($this->createOwningSideEntity());
        $this->getManager()->persist($this->getOwningSideEntity());

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideAdderValidateOwningSideMethods()
    {
        $this->validateMethods(
            $this->getOwningSideEntity(),
            [
                $this->getOwningSideIdGetterMethodValidation(),
                $this->getOwningSideSetterMethodValidation(),
                $this->getOwningSideGetterMethodValidation()
            ],
            $this->inverseSideAdderValidationName
        );

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideAdderValidateInverseSideMethods()
    {
        if ($this->isBidirectionnal()) {
            $this->validateMethods(
                $this->getInverseSideEntity(),
                [
                    $this->getInverseSideAdderMethodValidation(),
                    $this->getInverseSideGetterMethodValidation()
                ],
                $this->inverseSideAdderValidationName
            );
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function inverseSideAdderValidateAdder()
    {
        call_user_func([$this->getInverseSideEntity(), $this->getInverseSideAdder()], $this->getOwningSideEntity());
        $this->inverseSideAdderValidateOwningSideEntityIsInCollection();

        call_user_func([$this->getInverseSideEntity(), $this->getInverseSideAdder()], $this->getOwningSideEntity());
        $this->inverseSideAdderValidateOnlyOneOwningSideEntityIsInCollection();

        $this->inverseSideAdderAddOnlyOneOwningSideEntityValidation();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function inverseSideAdderValidateOwningSideEntityIsInCollection()
    {
        $mappedBy = call_user_func([$this->getOwningSideEntity(), $this->getOwningSideGetter()]);
        if ($mappedBy !== $this->getInverseSideEntity()) {
            $this->inverseSideAdderThrowAdderDoesntSetOwningSideEntityProperty();
        }

        $collection = call_user_func([$this->getInverseSideEntity(), $this->getInverseSideGetter()]);
        if ($collection instanceof Collection === false) {
            $this->inverseSideAdderThrowInverseSideGetterMustReturnCollection($collection);
        }

        $isInCollection = false;
        foreach ($collection as $entity) {
            if ($entity === $this->getOwningSideEntity()) {
                $isInCollection = true;
                break;
            }
        }
        if ($isInCollection === false) {
            $this->inverseSideAdderThrowAdderDoNotAddOwningSideEntity();
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function inverseSideAdderValidateOnlyOneOwningSideEntityIsInCollection()
    {
        $countEntities = 0;
        $entities = call_user_func(
            [
                $this->getInverseSideEntity(),
                $this->getInverseSideGetter()
            ],
            $this->getOwningSideEntity()
        );
        foreach ($entities as $entity) {
            if ($entity === $this->getOwningSideEntity()) {
                $countEntities++;
            }
        }

        if ($countEntities > 1) {
            $this->inverseSideAdderThrowAdderShouldNotAddSameOwningSideEntityTwice();
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function inverseSideAdderValidateFlush()
    {
        try {
            $this->getManager()->flush();
        } catch (ORMInvalidArgumentException $e) {
            $this->inverseSideAdderThrowAdderOrmInvalidArgumentException($e);
        }

        if (call_user_func([$this->getOwningSideEntity(), $this->getOwningSideIdGetter()]) === null) {
            $this->inverseSideAdderThrowOwningSideEntityIdIsNull();
        }

        $this->inverseSideAdderAddFlushValidation();

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideAdderValidateRefresh()
    {
        $this->getManager()->refresh($this->getInverseSideEntity());
        $this->getManager()->refresh($this->getOwningSideEntity());
        $this->inverseSideAdderValidateOwningSideEntityIsInCollection();

        $this->inverseSideAdderAddRefreshValidation();

        return $this;
    }

    /**
     * @throws ReportException
     */
    protected function inverseSideAdderThrowAdderDoesntSetOwningSideEntityProperty()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideAdder() . '() does not set ';
        $message .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty();
        $errorReport = new ErrorReport($message);

        $helpInverseSide = 'As Doctrine use Many side of relations to get informations at update / insert, ';
        $helpInverseSide .= $this->getInverseSideClassName() . '::' . $this->getInverseSideAdder() . '() should call ';
        $helpInverseSide .= $this->getOwningSideClassName() . '::' . $this->getOwningSideSetter();
        $helpInverseSide .= '($this). Otherwhise, ';
        $helpInverseSide .= $this->getOwningSideClassName() . ' will not be saved with relation to ';
        $helpInverseSide .= $this->getInverseSideClassName() . '.';
        $errorReport->addHelp($helpInverseSide);

        $helpOwningSideEntity = $this->getOwningSideClassName() . '::' . $this->getOwningSideSetter();
        $helpOwningSideEntity .= '() should set ';
        $helpOwningSideEntity .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty() . '.';
        $errorReport->addHelp($helpOwningSideEntity);

        $helpOwningSideEntity = $this->getOwningSideClassName() . '::' . $this->getOwningSideGetter();
        $helpOwningSideEntity .= '() should return ' . $this->getOwningSideClassName();
        $helpOwningSideEntity .= '::$' . $this->getOwningSideProperty() . '.';
        $errorReport->addHelp($helpOwningSideEntity);

        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideAdder());
        $errorReport->addMethodCode($this->getOwningSideEntity(), $this->getOwningSideSetter());
        $errorReport->addMethodCode($this->getOwningSideEntity(), $this->getOwningSideGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param mixed $collection
     * @throws ReportException
     */
    protected function inverseSideAdderThrowInverseSideGetterMustReturnCollection($collection) {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideGetter() . '() ';
        $message .= 'must return an instance of ' . Collection::class . ', ' . gettype($collection) . ' returned.';
        $errorReport = new ErrorReport($message);
        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function inverseSideAdderThrowAdderDoNotAddOwningSideEntity()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideAdder() . '() ';
        $message .= 'does not add ' . $this->getOwningSideClassName() . '.';
        $errorReport = new ErrorReport($message);

        $help = $this->getInverseSideClassName() . '::' . $this->getInverseSideAdder() . '() should add ';
        $help .= $this->getOwningSideClassName() . ' in ' . $this->getInverseSideClassName();
        $help .= '::$' . $this->getInverseSideProperty() . '.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideAdder());
        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function inverseSideAdderThrowAdderShouldNotAddSameOwningSideEntityTwice()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideAdder() . '() ';
        $message .= 'should not add same ' . $this->getOwningSideClassName() . ' instance twice.';
        $errorReport = new ErrorReport($message);

        $help = $this->getInverseSideClassName() . '::' . $this->getInverseSideAdder() . '() should use ';
        $help .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . '->contains().';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideAdder());
        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param ORMInvalidArgumentException $exception
     * @throws ReportException
     */
    protected function inverseSideAdderThrowAdderOrmInvalidArgumentException(ORMInvalidArgumentException $exception)
    {
        $message = 'ORMInvalidArgumentException occured after calling ';
        $message .= $this->getInverseSideClassName() . '::' . $this->getInverseSideAdder() . '(), ';
        $message .= 'then ' . get_class($this->getManager()) . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addError($exception->getMessage());
        $this->inverseSideAdderAddInverseSideEntityPersistError($errorReport);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function inverseSideAdderThrowOwningSideEntityIdIsNull()
    {
        $message = $this->getOwningSideClassName() . '::' . $this->getOwningSideIdGetter() . '() return null ';
        $message .= 'after calling ' . $this->getInverseSideClassName() . '::' . $this->getInverseSideAdder() . '(), ';
        $message .= 'then ' . get_class($this->getManager()) . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addMethodCode($this->getOwningSideEntity(), $this->getOwningSideIdGetter());
        $this->inverseSideAdderAddInverseSideEntityPersistError($errorReport);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param ErrorReport $errorReport
     * @return $this
     */
    protected function inverseSideAdderAddInverseSideEntityPersistError(ErrorReport $errorReport)
    {
        $propertyMetadata = $this
            ->getManager()
            ->getClassMetadata($this->getInverseSideClassName())
            ->associationMappings[$this->getInverseSideProperty()];

        if (in_array('persist', $propertyMetadata['cascade']) === false) {
            $help = 'You have to set "cascade: persist" on your mapping, ';
            $help .= 'or explicitly call ' . get_class($this->getManager()) . '::persist().';
            $errorReport->addHelp($help);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideAdderAddOnlyOneOwningSideEntityValidation()
    {
        $message = 'Add only one ' . $this->getOwningSideClassName() . ', even with mutiple calls with same instance.';
        $this->getValidationReport()->addValidation($this->inverseSideAdderValidationName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideAdderAddFlushValidation()
    {
        $message = get_class($this->getManager()) . '::flush() ';
        $message .= 'save ' . $this->getOwningSideClassName() . ' and ' . $this->getInverseSideClassName();
        $message .= ' correctly.';
        $this->getValidationReport()->addValidation($this->inverseSideAdderValidationName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideAdderAddRefreshValidation()
    {
        $message = $this->getOwningSideClassName() . ' is correctly reloaded in ';
        $message .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . ', ';
        $message .= 'even after calling ' . get_class($this->getManager()) . '::refresh() on all tested entities.';
        $this->getValidationReport()->addValidation($this->inverseSideAdderValidationName, $message);

        return $this;
    }
}
