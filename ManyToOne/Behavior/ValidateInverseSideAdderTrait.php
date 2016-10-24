<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use steevanb\DoctrineMappingValidator\Behavior\ValidateMethodsTrait;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait ValidateInverseSideAdderTrait
{
    use ValidateMethodsTrait;

    /** @var string */
    protected $inverseSideAdderValidationName;

    /** @var string */
    protected $inverseSideAdder;

    /**
     * @param object $entity
     * @return $this
     */
    abstract protected function setInverseSideEntity($entity);

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

    /** @return string */
    abstract protected function getOwningSideGetter();

    /** @return string */
    abstract protected function getOwningSideIdGetter();

    /** @return string */
    abstract protected function getInverseSideProperty();

    /** @return string */
    abstract protected function getInverseSideClassName();

    /** @return object */
    abstract protected function createInverseSideEntity();

    /** @return object */
    abstract protected function createOwningSideEntity();

    /** @return string */
    abstract protected function getOwningSideClassName();

    /** @return string */
    abstract protected function getInverseSideGetter();

    /** @return object */
    abstract protected function getInverseSideEntity();

    /** @return EntityManagerInterface */
    abstract protected function getManager();

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
        $this->inverseSideAdder = 'add' . ucfirst(substr($this->getInverseSideProperty(), 0, -1));
        $this->inverseSideAdderValidationName =
            $this->getInverseSideClassName() . '::' . $this->inverseSideAdder . '()';
        $this->setInverseSideEntity($this->createInverseSideEntity());

        $this->setOwningSideEntity($this->createOwningSideEntity());
        $this->getManager()->persist($this->getOwningSideEntity());

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideAdderValidateInverseSideMethods()
    {
        $adderMessage = 'You must create this method in order to add ' . $this->getOwningSideClassName() . ' in ';
        $adderMessage .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . ' collection.';
        $adderParameters = [ substr($this->getInverseSideProperty(), 0, -1) => [ $this->getOwningSideClassName() ] ];

        $getterMessage = 'You must create this method, in order to get ';
        $getterMessage .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . ' collection.';
        $getterParameters = [];

        $methods = [
            [ $this->inverseSideAdder, $adderMessage, $adderParameters ],
            [ $this->getInverseSideGetter(), $getterMessage, $getterParameters ],
        ];
        $this->validateMethods($this->getInverseSideEntity(), $methods, $this->inverseSideAdderValidationName);

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideAdderValidateOwningSideMethods()
    {
        $idGetterMessage = 'You must create this method in order to get ';
        $idGetterMessage .= $this->getOwningSideClassName() . '::$id';
        $idGetterParameters = [];

        $setterMessage = 'You must create this method in order to set ' . $this->getInverseSideClassName() . ' to ';
        $setterMessage .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty() . '.';
        $setterParameters = [ $this->getOwningSideProperty() => [ $this->getInverseSideClassName(), 'null' ] ];

        $getterMessage = 'You must create this method in order to get ';
        $getterMessage .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty() . '.';
        $getterParameters = [];

        $methods = [
            [ $this->getOwningSideIdGetter(), $idGetterMessage, $idGetterParameters ],
            [ $this->getOwningSideSetter(), $setterMessage, $setterParameters ],
            [ $this->getOwningSideGetter(), $getterMessage, $getterParameters ]
        ];
        $this->validateMethods($this->getOwningSideEntity(), $methods, $this->inverseSideAdderValidationName);

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function inverseSideAdderValidateAdder()
    {
        call_user_func([ $this->getInverseSideEntity(), $this->inverseSideAdder ], $this->getOwningSideEntity());
        $this->inverseSideAdderValidateOwningSideEntityIsInCollection();

        call_user_func([ $this->getInverseSideEntity(), $this->inverseSideAdder ], $this->getOwningSideEntity());
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
        $mappedBy = call_user_func([ $this->getOwningSideEntity(), $this->getOwningSideGetter() ]);
        if ($mappedBy !== $this->getInverseSideEntity()) {
            $this->inverseSideAdderThrowAdderDoesntSetOwningSideEntityProperty();
        }

        $collection = call_user_func([ $this->getInverseSideEntity(), $this->getInverseSideGetter() ]);
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

        if (call_user_func([ $this->getOwningSideEntity(), $this->getOwningSideIdGetter() ]) === null) {
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
        $message = $this->getInverseSideClassName() . '::' . $this->inverseSideAdder . '() does not set ';
        $message .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty();
        $errorReport = new ErrorReport($message);

        $helpInverseSide = 'As Doctrine use Many side of relations to get informations at update / insert, ';
        $helpInverseSide .= $this->getInverseSideClassName() . '::' . $this->inverseSideAdder . '() should call ';
        $helpInverseSide .= $this->getOwningSideClassName() . '::' . $this->getOwningSideSetter() . '($this). Otherwhise, ';
        $helpInverseSide .= $this->getOwningSideClassName() . ' will not be saved with relation to ' . $this->getInverseSideClassName() . '.';
        $errorReport->addHelp($helpInverseSide);

        $helpOwningSideEntity = $this->getOwningSideClassName() . '::' . $this->getOwningSideSetter() . '() should set ';
        $helpOwningSideEntity .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty() . '.';
        $errorReport->addHelp($helpOwningSideEntity);

        $helpOwningSideEntity = $this->getOwningSideClassName() . '::' . $this->getOwningSideGetter() . '() should return ';
        $helpOwningSideEntity .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty() . '.';
        $errorReport->addHelp($helpOwningSideEntity);

        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->inverseSideAdder);
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
        $message = $this->getInverseSideClassName() . '::' . $this->inverseSideAdder . '() ';
        $message .= 'does not add ' . $this->getOwningSideClassName() . '.';
        $errorReport = new ErrorReport($message);

        $help = $this->getInverseSideClassName() . '::' . $this->inverseSideAdder . '() should add ';
        $help .= $this->getOwningSideClassName() . ' in ' . $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . '.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->inverseSideAdder);
        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function inverseSideAdderThrowAdderShouldNotAddSameOwningSideEntityTwice()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->inverseSideAdder . '() ';
        $message .= 'should not add same ' . $this->getOwningSideClassName() . ' instance twice.';
        $errorReport = new ErrorReport($message);

        $help = $this->getInverseSideClassName() . '::' . $this->inverseSideAdder . '() should use ';
        $help .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . '->contains().';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->inverseSideAdder);
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
        $message .= $this->getInverseSideClassName() . '::' . $this->inverseSideAdder . '(), ';
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
        $message .= 'after calling ' . $this->getInverseSideClassName() . '::' . $this->inverseSideAdder . '(), ';
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
        $message .= 'save ' . $this->getOwningSideClassName() . ' and ' . $this->getInverseSideClassName() . ' correctly.';
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
