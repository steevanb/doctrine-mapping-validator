<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;

trait ValidateInverseSideSetterTrait
{
    /** @var string */
    protected $inverseSideSetterTestName;

    /** @var object */
    protected $owningSideEntity2;

    /** @return object */
    abstract protected function createInverseSideEntity();

    /** @return EntityManagerInterface */
    abstract protected function getManager();

    /**
     * @param object $entity
     * @return $this
     */
    abstract protected function setInverseSideEntity($entity);

    /** @return string*/
    abstract protected function getInverseSideClassName();

    /** @return object */
    abstract protected function getInverseSideEntity();

    /** @return string */
    abstract protected function getInverseSideSetter();

    /** @return string */
    abstract protected function getInverseSideGetter();

    /** @return string */
    abstract protected function getInverseSideProperty();

    /** @return string */
    abstract protected function getInverseSideAdder();

    /** @return string */
    abstract protected function getInverseSideClearer();

    /**
     * @param object $entity
     * @return $this
     */
    abstract protected function setOwningSideEntity($entity);

    /** @return object */
    abstract protected function getOwningSideEntity();

    /** @return object */
    abstract protected function getOwningSideSetter();

    /** @return object */
    abstract protected function getOwningSideGetter();

    /** @return string */
    abstract protected function getOwningSideClassName();

    /** @return string */
    abstract protected function getOwningSideProperty();

    /** @return string */
    abstract protected function getOwningSideIdGetter();

    /** @return object */
    abstract protected function createOwningSideEntity();

    /** @return Report */
    abstract protected function getReport();

    /** @return ValidationReport */
    abstract protected function getValidationReport();

    /** @return array */
    abstract protected function getInverseSideAdderMethodValidation();

    /** @return array */
    abstract protected function getInverseSideGetterMethodValidation();

    /**
     * @return $this
     * @throws ReportException
     */
    protected function validateInverseSideSetter()
    {
        $this
            ->inverseSideSetterInit()
            ->inverseSideSetterValidateOwningSideMethods()
            ->inverseSideSetterValidateInverseSideMethods()
            ->inverseSideSetterValidate()
            ->inverseSideSetterValidateOwningSideEntities()
            ->inverseSideSetterValidateReloadEntities();

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideSetterInit()
    {
        $this->inverseSideSetterTestName = $this->getInverseSideClassName() . '::' . $this->getInverseSideSetter() . '()';
        $this->setInverseSideEntity($this->createInverseSideEntity());

        $this->setOwningSideEntity($this->createOwningSideEntity());
        $this->getManager()->persist($this->getOwningSideEntity());

        $this->owningSideEntity2 = $this->createOwningSideEntity();
        $this->getManager()->persist($this->owningSideEntity2);

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideSetterValidateInverseSideMethods()
    {
        $this->validateMethods(
            $this->getInverseSideEntity(),
            [
                $this->getInverseSideAdderMethodValidation(),
                $this->getInverseSideGetterMethodValidation()
            ],
            $this->inverseSideAdderValidationName
        );

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideSetterValidateOwningSideMethods()
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
    protected function inverseSideSetterValidate()
    {
        call_user_func([ $this->getOwningSideEntity(), $this->getOwningSideSetter()], null);
        call_user_func([ $this->owningSideEntity2, $this->getOwningSideSetter()], null);

        $owningSideEntities = new ArrayCollection([ $this->getOwningSideEntity(), $this->owningSideEntity2 ]);
        call_user_func([ $this->getInverseSideEntity(), $this->getInverseSideSetter() ], $owningSideEntities);
        // try to call setter 2 times, to be sure it clear owningSideEntities before adding
        call_user_func([ $this->getInverseSideEntity(), $this->getInverseSideSetter() ], $owningSideEntities);

        $settedOwningSideEntities = call_user_func([ $this->getInverseSideEntity(), $this->getInverseSideGetter() ]);

        if (
            count($settedOwningSideEntities) === 2
            && isset($settedOwningSideEntities[2])
        ) {
            $this->inverseSideSetterThrowDoesntClearCollection();
        }

        if (
            count($settedOwningSideEntities) !== 2
            || $settedOwningSideEntities[0] !== $this->getOwningSideEntity()
            || $settedOwningSideEntities[1] !== $this->owningSideEntity2
            || call_user_func([ $this->getOwningSideEntity(), $this->getOwningSideGetter() ]) !== $this->getInverseSideEntity()
            || call_user_func([ $this->owningSideEntity2, $this->getOwningSideGetter() ]) !== $this->getInverseSideEntity()
        ) {
            $this->inverseSideSetterThrowDoesntSetProperty();
        }

        $this->inverseSideSetterAddSetPropertyValidation();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function inverseSideSetterValidateOwningSideEntities()
    {
        try {
            $this->getManager()->flush();
        } catch (ORMInvalidArgumentException $e) {
            $this->inverseSideSetterThrowOrmInvalidArgumentException($e);
        }

        if (
            call_user_func([ $this->getOwningSideEntity(), $this->getOwningSideIdGetter() ]) === null
            || call_user_func([ $this->owningSideEntity2, $this->getOwningSideIdGetter() ]) === null
        ) {
            $this->inverseSideSetterThrowOwningSideIdIsNullAfterSetterAndFlush();
        }

        $this->inverseSideSetterAddOwningSideFlushValidation();

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideSetterValidateReloadEntities()
    {
        $this->getManager()->refresh($this->getInverseSideEntity());
        $this->getManager()->refresh($this->getOwningSideEntity());
        $this->getManager()->refresh($this->owningSideEntity2);
        $this->inverseSideSetterValidateOnlyRightEntitiesAreInCollection();

        $this->inverseSideSetterAddFlushReloadValidation();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function inverseSideSetterValidateOnlyRightEntitiesAreInCollection()
    {
        $mappedBy = call_user_func([ $this->getOwningSideEntity(), $this->getOwningSideGetter() ]);
        if ($mappedBy !== $this->getInverseSideEntity()) {
            $this->inverseSideSetterThrowDoesntSetOwningSideProperty();
        }
        $mappedBy = call_user_func([ $this->owningSideEntity2, $this->getOwningSideGetter() ]);
        if ($mappedBy !== $this->getInverseSideEntity()) {
            $this->inverseSideSetterThrowDoesntSetOwningSideProperty();
        }

        /** @var Collection $collection */
        $collection = call_user_func([ $this->getInverseSideEntity(), $this->getInverseSideGetter() ]);
        if ($collection instanceof Collection === false) {
            $this->inverseSideSetterThrowGetterMustReturnCollection($collection);
        }
        if (
            $collection->first() !== $this->getOwningSideEntity()
            || $collection->next() !== $this->owningSideEntity2
        ) {
            $this->inverseSideSetterThrowDoesntAddOwningSideEntities();
        }

        return $this;
    }

    /**
     * @throws ReportException
     */
    protected function inverseSideSetterThrowDoesntAddOwningSideEntities()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideSetter() . '() ';
        $message .= 'does not set ' . $this->getOwningSideClassName() . '.';
        $errorReport = new ErrorReport($message);

        $help = $this->getInverseSideClassName() . '::' . $this->getInverseSideSetter() . '() should set ';
        $help .= $this->getOwningSideClassName() . ' in ' . $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . '.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideSetter());
        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param mixed $collection
     * @throws ReportException
     */
    protected function inverseSideSetterThrowGetterMustReturnCollection($collection) {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideGetter() . '() ';
        $message .= 'must return an instance of ' . Collection::class . ', ' . gettype($collection) . ' returned.';
        $errorReport = new ErrorReport($message);
        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function inverseSideSetterThrowDoesntSetOwningSideProperty()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideSetter() . '() does not set ';
        $message .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty();
        $errorReport = new ErrorReport($message);

        $helpInverseSideEntity = 'As Doctrine use Many side of relations to get informations at update / insert, ';
        $helpInverseSideEntity .= $this->getInverseSideClassName() . '::' . $this->getInverseSideSetter() . '() should call ';
        $helpInverseSideEntity .= $this->getOwningSideClassName() . '::' . $this->getOwningSideSetter() . '($this). Otherwhise, ';
        $helpInverseSideEntity .= $this->getOwningSideClassName() . ' will not be saved with relation to ' . $this->getInverseSideClassName() . '.';
        $errorReport->addHelp($helpInverseSideEntity);

        $helpOwningSideEntity = $this->getOwningSideClassName() . '::' . $this->getOwningSideSetter() . '() should set ';
        $helpOwningSideEntity .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty() . '.';
        $errorReport->addHelp($helpOwningSideEntity);

        $helpOwningSideEntity = $this->getOwningSideClassName() . '::' . $this->getOwningSideGetter() . '() should return ';
        $helpOwningSideEntity .= $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty() . '.';
        $errorReport->addHelp($helpOwningSideEntity);

        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideSetter());
        $errorReport->addMethodCode($this->getOwningSideEntity(), $this->getOwningSideSetter());
        $errorReport->addMethodCode($this->getOwningSideEntity(), $this->getOwningSideGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function inverseSideSetterThrowOwningSideIdIsNullAfterSetterAndFlush()
    {
        $message = $this->getOwningSideClassName() . '::$id is null after calling ';
        $message .= $this->getInverseSideClassName() . '::' . $this->getInverseSideSetter() . '(), ';
        $message .= 'then ' . get_class($this->getManager()) . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addMethodCode($this->getOwningSideEntity(), $this->getOwningSideIdGetter());
        $this->inverseSideSetterAddPersistHelp($errorReport);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param ORMInvalidArgumentException $exception
     * @throws ReportException
     */
    protected function inverseSideSetterThrowOrmInvalidArgumentException(ORMInvalidArgumentException $exception)
    {
        $message = 'ORMInvalidArgumentException occured after calling ';
        $message .= $this->getInverseSideClassName() . '::' . $this->getInverseSideSetter() . '(), ';
        $message .= 'then ' . get_class($this->getManager()) . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addError($exception->getMessage());
        $this->inverseSideSetterAddPersistHelp($errorReport);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param ErrorReport $errorReport
     * @return $this
     */
    protected function inverseSideSetterAddPersistHelp(ErrorReport $errorReport)
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
     * @throws ReportException
     */
    protected function inverseSideSetterThrowDoesntClearCollection()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideClearer() . '() doest not reset ';
        $message .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . ' indexes.';
        $errorReport = new ErrorReport($message);

        $help = $this->getInverseSideClassName() . '::' . $this->getInverseSideClearer() . '() should call ';
        $help .= '$this->' . $this->getInverseSideProperty() . '->clear() after $this->' . $this->getInverseSideAdder() . '()';
        $help .= ' to reset Collection indexes.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideSetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function inverseSideSetterThrowDoesntSetProperty()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideSetter() . '() doest not set ';
        $message .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty();
        $errorReport = new ErrorReport($message);

        $help = 'This method should call $this->' . $this->getInverseSideClearer() . '(), and ';
        $help .= $this->getInverseSideAdder() . '() for each ' . $this->getOwningSideClassName() . ' passed.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->getInverseSideEntity(), $this->getInverseSideSetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @return $this
     */
    protected function inverseSideSetterAddOwningSideFlushValidation()
    {
        $message = get_class($this->getManager()) . '::flush() ';
        $message .= 'save ' . $this->getInverseSideClassName() . ' and ' . $this->getOwningSideClassName() . ' correctly.';
        $this->getValidationReport()->addValidation($this->inverseSideSetterTestName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideSetterAddSetPropertyValidation()
    {
        $message = 'Set ' . $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . ' correctly, ';
        $message .= 'even with multiple calls with same instances.';
        $this->getValidationReport()->addValidation($this->inverseSideSetterTestName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideSetterAddFlushReloadValidation()
    {
        $message = $this->getOwningSideClassName() . ' is correctly reloaded in ';
        $message .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . ', ';
        $message .= 'even after calling ' . get_class($this->getManager()) . '::refresh() on all tested entities.';
        $this->getValidationReport()->addValidation($this->inverseSideSetterTestName, $message);

        return $this;
    }
}
