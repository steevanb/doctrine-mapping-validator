<?php

namespace steevanb\DoctrineMappingValidator\OneToMany\Behavior;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\ORMInvalidArgumentException;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait ValidateLeftEntityAdderTrait
{
    use PropertiesTrait;

    /**
     * @return $this
     */
    protected function validateLeftEntityAdder()
    {
        $this
            ->assertLeftEntityAdder()
            ->assertFlushAdderRightEntity()
            ->assertReloadAdderRightEntity();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertLeftEntityAdder()
    {
        call_user_func([ $this->leftEntity, $this->leftEntityAdder ], $this->rightEntity);
        $this->assertRightEntityIsInCollection();

        call_user_func([ $this->leftEntity, $this->leftEntityAdder ], $this->rightEntity);
        $this->assertOnlyOneRightEntityIsInCollection();

        $this->addPassedAdderOnlyOneRightEntityTest();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertRightEntityIsInCollection()
    {
        $mappedBy = call_user_func([ $this->rightEntity, $this->rightEntityGetter ]);
        if ($mappedBy !== $this->leftEntity) {
            $this->throwLeftEntityAdderDoesntSetRightEntityProperty();
        }

        $collection = call_user_func([ $this->leftEntity, $this->leftEntityGetter ]);
        if ($collection instanceof Collection === false) {
            $this->throwAdderLeftEntityGetterMustReturnCollection($collection);
        }

        $isInCollection = false;
        foreach ($collection as $entity) {
            if ($entity === $this->rightEntity) {
                $isInCollection = true;
                break;
            }
        }
        if ($isInCollection === false) {
            $this->throwLeftEntityAdderDoesntAddRightEntity();
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertOnlyOneRightEntityIsInCollection()
    {
        $countEntities = 0;
        foreach (call_user_func([ $this->leftEntity, $this->leftEntityGetter ], $this->rightEntity) as $entity) {
            if ($entity === $this->rightEntity) {
                $countEntities++;
            }
        }

        if ($countEntities > 1) {
            $this->throwLeftEntityAdderShouldNotAddSameRightEntityTwice();
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertFlushAdderRightEntity()
    {
        try {
            $this->manager->flush();
        } catch (ORMInvalidArgumentException $e) {
            $this->throwAdderOrmInvalidArgumentException($e);
        }

        if (call_user_func([ $this->rightEntity, $this->rightEntityIdGetter ]) === null) {
            $this->throwRightEntityIdIsNullAfterLeftEntityAdderAndFlush();
        }

        $this->addPasseAdderFlushTest();

        return $this;
    }

    /**
     * @return $this
     */
    protected function assertReloadAdderRightEntity()
    {
        $this->manager->refresh($this->leftEntity);
        $this->manager->refresh($this->rightEntity);
        $this->assertRightEntityIsInCollection();

        $this->addPassedAdderFlushReloadTest();

        return $this;
    }

    /**
     * @throws ReportException
     */
    protected function throwLeftEntityAdderDoesntSetRightEntityProperty()
    {
        $message = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() does not set ';
        $message .= $this->rightEntityClass . '::$' . $this->rightEntityProperty;
        $errorReport = new ErrorReport($message);

        $helpLeftentity = 'As Doctrine use Many side of relations to get informations at update / insert, ';
        $helpLeftentity .= $this->leftEntityClass . '::' . $this->leftEntityAdder . '() should call ';
        $helpLeftentity .= $this->rightEntityClass . '::' . $this->rightEntitySetter . '($this). Otherwhise, ';
        $helpLeftentity .= $this->rightEntityClass . ' will not be saved with relation to ' . $this->leftEntityClass . '.';
        $errorReport->addHelp($helpLeftentity);

        $helpRightEntity = $this->rightEntityClass . '::' . $this->rightEntitySetter . '() should set ';
        $helpRightEntity .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';
        $errorReport->addHelp($helpRightEntity);

        $helpRightEntity = $this->rightEntityClass . '::' . $this->rightEntityGetter . '() should return ';
        $helpRightEntity .= $this->rightEntityClass . '::$' . $this->rightEntityProperty . '.';
        $errorReport->addHelp($helpRightEntity);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityAdder);
        $errorReport->addMethodCode($this->rightEntity, $this->rightEntitySetter);
        $errorReport->addMethodCode($this->rightEntity, $this->rightEntityGetter);

        throw new ReportException($this->report, $errorReport);
    }

    /**
     * @param mixed $collection
     * @throws ReportException
     */
    protected function throwAdderLeftEntityGetterMustReturnCollection($collection) {
        $message = $this->leftEntityClass . '::' . $this->leftEntityGetter . '() ';
        $message .= 'must return an instance of ' . Collection::class . ', ' . gettype($collection) . ' returned.';
        $errorReport = new ErrorReport($message);
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

        throw new ReportException($this->report, $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwLeftEntityAdderDoesntAddRightEntity()
    {
        $message = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() ';
        $message .= 'does not add ' . $this->rightEntityClass . '.';
        $errorReport = new ErrorReport($message);

        $help = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() should add ';
        $help .= $this->rightEntityClass . ' in ' . $this->leftEntityClass . '::$' . $this->leftEntityProperty . '.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityAdder);
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

        throw new ReportException($this->report, $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwLeftEntityAdderShouldNotAddSameRightEntityTwice()
    {
        $message = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() ';
        $message .= 'should not add same ' . $this->rightEntityClass . ' instance twice.';
        $errorReport = new ErrorReport($message);

        $help = $this->leftEntityClass . '::' . $this->leftEntityAdder . '() should use ';
        $help .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . '->contains().';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityAdder);
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

        throw new ReportException($this->report, $errorReport);
    }

    /**
     * @param ORMInvalidArgumentException $exception
     * @throws ReportException
     */
    protected function throwAdderOrmInvalidArgumentException(ORMInvalidArgumentException $exception)
    {
        $message = 'ORMInvalidArgumentException occured after calling ';
        $message .= $this->leftEntityClass . '::' . $this->leftEntityAdder . '(), ';
        $message .= 'then ' . $this->managerClass . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addError($exception->getMessage());
        $this->addLeftEntityPersistError($errorReport);

        throw new ReportException($this->report, $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwRightEntityIdIsNullAfterLeftEntityAdderAndFlush()
    {
        $message = $this->rightEntityClass . '::$id is null after calling ';
        $message .= $this->leftEntityClass . '::' . $this->leftEntityAdder . '(), ';
        $message .= 'then ' . $this->managerClass . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addMethodCode($this->rightEntity, $this->rightEntityIdGetter);
        $this->addLeftEntityPersistError($errorReport);

        throw new ReportException($this->report, $errorReport);
    }

    /**
     * @param ErrorReport $errorReport
     * @return $this
     */
    protected function addLeftEntityPersistError(ErrorReport $errorReport)
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
     * @return $this
     */
    protected function addPassedAdderOnlyOneRightEntityTest()
    {
        $message = 'Add only one ' . $this->rightEntityClass . ', even with mutiple calls with same instance.';
        $this->passedReport->addTest($this->leftEntityAdderTestName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addPasseAdderFlushTest()
    {
        $message = $this->managerClass . '::flush() ';
        $message .= 'save ' . $this->leftEntityClass . ' and ' . $this->rightEntityClass . ' correctly.';
        $this->passedReport->addTest($this->leftEntityAdderTestName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addPassedAdderFlushReloadTest()
    {
        $message = $this->rightEntityClass . ' is correctly reloaded in ';
        $message .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ', ';
        $message .= 'even after calling ' . $this->managerClass . '::refresh() on all tested entities.';
        $this->passedReport->addTest($this->leftEntityAdderTestName, $message);

        return $this;
    }
}
