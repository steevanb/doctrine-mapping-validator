<?php

namespace steevanb\DoctrineMappingValidator\OneToMany\Behavior;

use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait ValidateLeftEntityRemoverTrait
{
    use PropertiesTrait;

    /**
     * @return $this
     * @throws ReportException
     * @throws \Exception
     */
    protected function validateLeftEntityRemover()
    {
        if (call_user_func([ $this->rightEntity, $this->rightEntityIdGetter ]) === null) {
            throw new \Exception('$this->rightEntity->' . $this->rightEntityIdGetter . '() should not be null.');
        }

        $this
            ->removeRightEntity()
            ->assertRightEntityIsNotInCollection()
            ->assertRightEntityLinkIsNull()
            ->flushRemoveRightEntity();

        $this->manager->refresh($this->leftEntity);

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function removeRightEntity()
    {
        try {
            call_user_func([ $this->leftEntity, $this->leftEntityRemover ], $this->rightEntity);
        } catch (\Throwable $e) {
            $this->throwUnknowException($e);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertRightEntityLinkIsNull()
    {
        $rightEntityLinkedEntity = call_user_func([ $this->rightEntity, $this->rightEntityGetter ]);
        if ($rightEntityLinkedEntity !== null) {
            $this->throwLeftEntityRemoverDoesntSetRightEntityPropertyToNull();
        }

        $this->addPassedRightEntityPropertyToNullTest();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertRightEntityIsNotInCollection()
    {
        foreach (call_user_func([ $this->leftEntity, $this->leftEntityGetter ]) as $entity) {
            if ($entity === $this->rightEntity) {
                $this->throwLeftEntityRemoverDoesntRemoveRightEntityInCollection();
            }
        }

        $this->addPassedRemoveRightEntityInLeftEntityCollection();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function flushRemoveRightEntity()
    {
        $rightEntityId = call_user_func([ $this->rightEntity, $this->rightEntityIdGetter ]);
        if ($rightEntityId === null) {
            $this->throwRightEntityIdGetterShouldReturnInteger();
        }

        try {
            $this->manager->flush();
        } catch (NotNullConstraintViolationException $e) {
            $this->throwExceptionOccuredWhileRemovingRightEntity($e);
        }

        if (call_user_func([ $this->rightEntity, $this->rightEntityIdGetter ]) !== null) {
            $this->throwRightEntityIdGetterShouldReturnNull();
        }

        if ($this->manager->contains($this->rightEntity)) {
            $this->throwRightEntityShouldNotBeManaged();
        }

        if ($this->getRightEntityById($rightEntityId) !== null) {
            $this->throwRightEntityShouldBeDeleted();
        }

        $this->addPassedFlushRemoveRightEntity();

        return $this;
    }

    /**
     * @param int $id
     * @return object|null
     */
    protected function getRightEntityById($id)
    {
        return $this->manager->getRepository($this->rightEntityClass)->findOneBy([ 'id' => $id ]);
    }

    /**
     * @param \Throwable $exception
     * @throws ReportException
     */
    protected function throwUnknowException(\Throwable $exception)
    {
        $message = get_class($exception) . ' occured while calling ';
        $message .= $this->leftEntityClass . '::' . $this->leftEntityRemover. '().';
        $errorReport = new ErrorReport($message);

        $errorReport->addError($exception->getMessage());

        $help = 'It can happen if ' . $this->rightEntityClass . '::';
        $help .= $this->rightEntitySetter . '() does not allow null as first parameter.';
        $help .= ' This is required to remove link between ' . $this->rightEntityClass . ' and ';
        $help .= $this->leftEntityClass . '.';
        $errorReport->addHelp($help);

        $errorReport->addCodeLinePreview($exception->getFile(), $exception->getLine());
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);
        $errorReport->addMethodCode($this->rightEntity, $this->rightEntitySetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwLeftEntityRemoverDoesntSetRightEntityPropertyToNull()
    {
        $message = $this->leftEntityClass . '::' . $this->leftEntityRemover . '()';
        $message .= ' does not call ' . $this->rightEntityClass . '::';
        $message .= $this->rightEntitySetter . '(null).';
        $errorReport = new ErrorReport($message);

        $helpLeftentity = 'As Doctrine use Many side of relations to get informations at update / insert, ';
        $helpLeftentity .= $this->leftEntityClass . '::' . $this->leftEntityRemover . '() should call ';
        $helpLeftentity .= $this->rightEntityClass . '::' . $this->rightEntitySetter . '(null). Otherwhise, ';
        $helpLeftentity .= $this->rightEntityClass . ' will not be removed by your manager.';
        $errorReport->addHelp($helpLeftentity);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);
        $errorReport->addMethodCode($this->rightEntity, $this->rightEntitySetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwLeftEntityRemoverDoesntRemoveRightEntityInCollection()
    {
        $message = $this->leftEntityClass . '::' . $this->leftEntityRemover . '() should remove ';
        $message .= $this->rightEntityClass . ' in ';
        $message .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' collection.';
        $errorReport = new ErrorReport($message);

        $help = 'You should call $this->' . $this->leftEntityProperty . '->removeElement() ';
        $help .= 'in ' . $this->leftEntityClass . '::' . $this->leftEntityRemover . '()';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwRightEntityIdGetterShouldReturnInteger()
    {
        $message = $this->rightEntityClass . '::' . $this->rightEntityIdGetter . '() should return integer.';
        $errorReport = new ErrorReport($message);

        $errorReport->addMethodCode($this->rightEntity, $this->rightEntityIdGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param NotNullConstraintViolationException $exception
     * @throws ReportException
     */
    protected function throwExceptionOccuredWhileRemovingRightEntity(NotNullConstraintViolationException $exception)
    {
        $message = get_class($exception) . ' occurend while removing ' . $this->rightEntityClass . '.';
        $errorReport = new ErrorReport($message);

        $help = 'You have to set "orphanRemoval: true" on your mapping, ';
        $help .= 'or explicitly call ' . $this->managerClass . '::remove().';
        $errorReport->addHelp($help);

        $errorReport->addError($exception->getMessage());
        $errorReport->addCodeLinePreview($exception->getFile(), $exception->getLine());
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwRightEntityIdGetterShouldReturnNull()
    {
        $message = $this->rightEntityClass . '::' . $this->rightEntityIdGetter . '() should return null ';
        $message .= 'after calling ' . $this->leftEntityClass . '::' . $this->leftEntityRemover . '() ';
        $message .= 'and ' . $this->managerClass . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);
        $errorReport->addMethodCode($this->rightEntity, $this->rightEntityIdGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwRightEntityShouldNotBeManaged()
    {
        $message = '$this->rightEntity should not be managed, ';
        $message .= 'after calling ' . $this->leftEntityClass . '::' . $this->leftEntityRemover . '() ';
        $message .= 'and ' . $this->managerClass . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityRemover);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function throwRightEntityShouldBeDeleted()
    {
        $message = 'RightEntity should be deleted after calling ' . $this->managerClass . '::flush()';
        $errorReport = new ErrorReport($message);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @return $this
     */
    protected function addPassedRightEntityPropertyToNullTest()
    {
        $message = 'Set ' . $this->rightEntityClass . '::$' . $this->rightEntityProperty . ' to null.';
        $this->validationReport->addValidation($this->leftEntityRemoverTestName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addPassedRemoveRightEntityInLeftEntityCollection()
    {
        $message = 'Remove ' . $this->rightEntityClass;
        $message .= ' in ' . $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' correctly.';
        $this->validationReport->addValidation($this->leftEntityRemoverTestName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addPassedFlushRemoveRightEntity()
    {
        $message = $this->managerClass . '::flush() remove ' . $this->rightEntityClass . ' correctly.';
        $this->validationReport->addValidation($this->leftEntityRemoverTestName, $message);

        return $this;
    }
}
