<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\ORMInvalidArgumentException;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait ValidateLeftEntitySetterTrait
{
    use PropertiesTrait;

    /**
     * @return $this
     * @throws ReportException
     */
    protected function validateLeftEntitySetter()
    {
        $this
            ->assertLeftEntitySetter()
            ->assertFlushSetterRightEntities()
            ->assertReloadSetterRightEntities();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertLeftEntitySetter()
    {
        call_user_func([ $this->rightEntity, $this->rightEntitySetter], null);
        call_user_func([ $this->rightEntity2, $this->rightEntitySetter], null);

        $rightEntities = new ArrayCollection([ $this->rightEntity, $this->rightEntity2 ]);
        call_user_func([ $this->leftEntity, $this->leftEntitySetter ], $rightEntities);
        // try to call setter 2 times, to be sure it clear rightEntites before adding
        call_user_func([ $this->leftEntity, $this->leftEntitySetter ], $rightEntities);

        $settedRightEnties = call_user_func([ $this->leftEntity, $this->leftEntityGetter ]);
        if (
            count($settedRightEnties) !== 2
            || $settedRightEnties[0] !== $this->rightEntity
            || $settedRightEnties[1] !== $this->rightEntity2
            || call_user_func([ $this->rightEntity, $this->rightEntityGetter ]) !== $this->leftEntity
            || call_user_func([ $this->rightEntity2, $this->rightEntityGetter ]) !== $this->leftEntity
        ) {
            $this->throwLeftEntitySetterDoesntSetProperty();
        }

        $this->addPassedSetLeftEntityProperty();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertFlushSetterRightEntities()
    {
        try {
            $this->manager->flush();
        } catch (ORMInvalidArgumentException $e) {
            $this->throwSetterOrmInvalidArgumentException($e);
        }

        if (
            call_user_func([ $this->rightEntity, $this->rightEntityIdGetter ]) === null
            || call_user_func([ $this->rightEntity2, $this->rightEntityIdGetter ]) === null
        ) {
            $this->throwRightEntityIdIsNullAfterLeftEntitySetterAndFlush();
        }

        $this->addPasseSetterRightEntityFlushTest();

        return $this;
    }

    /**
     * @return $this
     */
    protected function assertReloadSetterRightEntities()
    {
        $this->manager->refresh($this->leftEntity);
        $this->manager->refresh($this->rightEntity);
        $this->manager->refresh($this->rightEntity2);
        $this->assertOnlyRightEntitiesAreInCollection();

        $this->addPassedSetterFlushReloadTest();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertOnlyRightEntitiesAreInCollection()
    {
        $mappedBy = call_user_func([ $this->rightEntity, $this->rightEntityGetter ]);
        if ($mappedBy !== $this->leftEntity) {
            $this->throwLeftEntitySetterDoesntSetRightEntityProperty();
        }
        $mappedBy = call_user_func([ $this->rightEntity2, $this->rightEntityGetter ]);
        if ($mappedBy !== $this->leftEntity) {
            $this->throwLeftEntitySetterDoesntSetRightEntityProperty();
        }

        /** @var Collection $collection */
        $collection = call_user_func([ $this->leftEntity, $this->leftEntityGetter ]);
        if ($collection instanceof Collection === false) {
            $this->throwSetterLeftEntityGetterMustReturnCollection($collection);
        }
        if (
            $collection->first() !== $this->rightEntity
            || $collection->next() !== $this->rightEntity2
        ) {
            $this->throwLeftEntitySetterDoesntAddRightEntities();
        }

        return $this;
    }

    /**
     * @throws ReportException
     */
    protected function throwLeftEntitySetterDoesntAddRightEntities()
    {
        $message = $this->leftEntityClass . '::' . $this->leftEntitySetter . '() ';
        $message .= 'does not set ' . $this->rightEntityClass . '.';
        $errorReport = new ErrorReport($message);

        $help = $this->leftEntityClass . '::' . $this->leftEntitySetter . '() should set ';
        $help .= $this->rightEntityClass . ' in ' . $this->leftEntityClass . '::$' . $this->leftEntityProperty . '.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntitySetter);
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param mixed $collection
     * @throws ReportException
     */
    protected function throwSetterLeftEntityGetterMustReturnCollection($collection) {
        $message = $this->leftEntityClass . '::' . $this->leftEntityGetter . '() ';
        $message .= 'must return an instance of ' . Collection::class . ', ' . gettype($collection) . ' returned.';
        $errorReport = new ErrorReport($message);
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwLeftEntitySetterDoesntSetRightEntityProperty()
    {
        $message = $this->leftEntityClass . '::' . $this->leftEntitySetter . '() does not set ';
        $message .= $this->rightEntityClass . '::$' . $this->rightEntityProperty;
        $errorReport = new ErrorReport($message);

        $helpLeftentity = 'As Doctrine use Many side of relations to get informations at update / insert, ';
        $helpLeftentity .= $this->leftEntityClass . '::' . $this->leftEntitySetter . '() should call ';
        $helpLeftentity .= $this->rightEntityClass . '::' . $this->rightEntitySetter . '($this). Otherwhise, ';
        $helpLeftentity .= $this->rightEntityClass . ' will not be saved with relation to ' . $this->leftEntityClass . '.';
        $errorReport->addHelp($helpLeftentity);

        $helpRightEntity = $this->rightEntityClass . '::' . $this->rightEntitySetter . '() should set ';
        $helpRightEntity .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';
        $errorReport->addHelp($helpRightEntity);

        $helpRightEntity = $this->rightEntityClass . '::' . $this->rightEntityGetter . '() should return ';
        $helpRightEntity .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';
        $errorReport->addHelp($helpRightEntity);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntitySetter);
        $errorReport->addMethodCode($this->rightEntity, $this->rightEntitySetter);
        $errorReport->addMethodCode($this->rightEntity, $this->rightEntityGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwRightEntityIdIsNullAfterLeftEntitySetterAndFlush()
    {
        $message = $this->rightEntityClass . '::$id is null after calling ';
        $message .= $this->leftEntityClass . '::' . $this->leftEntitySetter . '(), ';
        $message .= 'then ' . $this->managerClass . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addMethodCode($this->rightEntity, $this->rightEntityIdGetter);
        $this->setLeftEntityPersistError($errorReport);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @return $this
     */
    protected function addPasseSetterRightEntityFlushTest()
    {
        $message = $this->managerClass . '::flush() ';
        $message .= 'save ' . $this->leftEntityClass . ' and ' . $this->rightEntityClass . ' correctly.';
        $this->validationReport->addValidation($this->leftEntitySetterTestName, $message);

        return $this;
    }

    /**
     * @param ORMInvalidArgumentException $exception
     * @throws ReportException
     */
    protected function throwSetterOrmInvalidArgumentException(ORMInvalidArgumentException $exception)
    {
        $message = 'ORMInvalidArgumentException occured after calling ';
        $message .= $this->leftEntityClass . '::' . $this->leftEntitySetter . '(), ';
        $message .= 'then ' . $this->managerClass . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addError($exception->getMessage());
        $this->setLeftEntityPersistError($errorReport);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param ErrorReport $errorReport
     * @return $this
     */
    protected function setLeftEntityPersistError(ErrorReport $errorReport)
    {
        $propertyMetadata = $this
            ->manager
            ->getClassMetadata($this->leftEntityClass)
            ->associationMappings[$this->leftEntityProperty];

        if (in_array('persist', $propertyMetadata['cascade']) === false) {
            $help = 'You have to set "cascade: persist" on your mapping, ';
            $help .= 'or explicitly call ' . $this->managerClass . '::persist().';
            $errorReport->addHelp($help);
        }

        return $this;
    }

    /**
     * @throws ReportException
     */
    protected function throwLeftEntitySetterDoesntSetProperty()
    {
        $message = $this->leftEntityClass . '::' . $this->leftEntitySetter . '() doest not set ';
        $message .= $this->leftEntityClass . '::$' . $this->leftEntityProperty;
        $errorReport = new ErrorReport($message);

        $help = 'This method should call $this->' . $this->leftEntityClearer . '(), and ';
        $help .= $this->leftEntityAdder . '() for each ' . $this->rightEntityClass . ' passed.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntitySetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @return $this
     */
    protected function addPassedSetLeftEntityProperty()
    {
        $message = 'Set ' . $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' correctly, ';
        $message .= 'even with multiple calls with same instances.';
        $this->validationReport->addValidation($this->leftEntitySetterTestName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addPassedSetterFlushReloadTest()
    {
        $message = $this->rightEntityClass . ' is correctly reloaded in ';
        $message .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ', ';
        $message .= 'even after calling ' . $this->managerClass . '::refresh() on all tested entities.';
        $this->validationReport->addValidation($this->leftEntitySetterTestName, $message);

        return $this;
    }
}
