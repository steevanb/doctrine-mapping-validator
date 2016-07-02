<?php

namespace steevanb\DoctrineMappingValidator\OneToMany\Behavior;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\ORMInvalidArgumentException;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait ValidateLeftEntityAdderTrait
{
    use CreateEntityTrait;
    use ValidateMethodsTrait;

    /** @var object */
    protected $leftEntity;

    /** @var object */
    protected $rightEntity;

    /** @var string */
    protected $leftEntityAdderValidationName;

    /** @var string */
    protected $leftEntityAdderMethod;

    /**
     * @return string
     */
    abstract protected function getLeftEntityProperty();

    /**
     * @return string
     */
    abstract protected function getRightEntityPropperty();

    /**
     * @return Report
     */
    abstract protected function getReport();

    /**
     * @return $this
     */
    protected function validateLeftEntityAdder()
    {
        $this
            ->leftEntityAdderInit()
            ->leftEntityAdderValidateLeftEntityMethods()
            ->leftEntityAdderValidateRightEntityMethods()
            ->leftEntityAdderValidateAdder()
            ->leftEntityAdderValidateFlush()
            ->leftEntityAdderValidateRefresh();

        return $this;
    }

    /**
     * @return $this
     */
    protected function leftEntityAdderInit()
    {
        $this->leftEntity = $this->createLeftEntity();
        $this->rightEntity = $this->createRightEntity();
        $this->leftEntityAdderMethod = 'add' . ucfirst(substr($this->getLeftEntityProperty(), 0, -1));
        $this->leftEntityAdderValidationName =
            $this->getLeftEntityClassName() . '::' . $this->leftEntityAdderMethod . '()';

        return $this;
    }

    /**
     * @return $this
     */
    protected function leftEntityAdderValidateLeftEntityMethods()
    {
        $adderMessage = 'You must create this method in order to add ' . $this->getRightEntityClassName() . ' in ';
        $adderMessage .= $this->getLeftEntityClassName() . '::$' . $this->getLeftEntityProperty() . ' collection.';
        $adderParameters = [ substr($this->getLeftEntityProperty(), 0, -1) => [ $this->getRightEntityClassName() ] ];

        $getterMessage = 'You must create this method, in order to get ';
        $getterMessage .= $this->getLeftEntityClassName() . '::$' . $this->getLeftEntityProperty() . ' collection.';
        $getterParameters = [];

        $methods = [
            [ $this->leftEntityAdderMethod, $adderMessage, $adderParameters ],
            [ 'get' . ucfirst($this->getLeftEntityProperty()), $getterMessage, $getterParameters ],
        ];

        $this->validateMethods($this->leftEntity, $methods, $this->leftEntityAdderValidationName);

        return $this;
    }

    /**
     * @return $this
     */
    protected function leftEntityAdderValidateRightEntityMethods()
    {
        $idGetterMessage = 'You must create this method in order to get ';
        $idGetterMessage .= $this->getRightEntityClassName() . '::$id';
        $idGetterParameters = [];

        $setterMessage = 'You must create this method in order to set ' . $this->getLeftEntityClassName() . ' to ';
        $setterMessage .= $this->getRightEntityClassName() . '::$' . $this->getRightEntityPropperty() . '.';
        $setterParameters = [ $this->getRightEntityPropperty() => [ $this->getLeftEntityClassName(), 'null' ] ];

        $getterMessage = 'You must create this method in order to get ';
        $getterMessage .= $this->getRightEntityClassName() . '::$' . $this->getRightEntityPropperty() . '.';
        $getterParameters = [];

        $methods = [
            [ 'getId', $idGetterMessage, $idGetterParameters ],
            [ 'set' . ucfirst($this->getRightEntityPropperty()), $setterMessage, $setterParameters ],
            [ 'get' . ucfirst($this->getRightEntityPropperty()), $getterMessage, $getterParameters ]
        ];
        $this->validateMethods($this->rightEntity, $methods, $this->leftEntityAdderValidationName);

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function leftEntityAdderValidateAdder()
    {
        call_user_func([ $this->leftEntity, $this->leftEntityAdderMethod ], $this->rightEntity);
        $this->leftEntityAdderValidateRightEntityIsInCollection();

        call_user_func([ $this->leftEntity, $this->leftEntityAdderMethod ], $this->rightEntity);
        $this->leftEntityAdderValidateOnlyOneRightEntityIsInCollection();

        $this->leftEntityAdderAddOnlyOneRightEntityValidation();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function leftEntityAdderValidateRightEntityIsInCollection()
    {
        $mappedBy = call_user_func([ $this->rightEntity, $this->rightEntityGetter ]);
        if ($mappedBy !== $this->leftEntity) {
            $this->leftEntityAdderThrowAdderDoesntSetRightEntityProperty();
        }

        $collection = call_user_func([ $this->leftEntity, $this->leftEntityGetter ]);
        if ($collection instanceof Collection === false) {
            $this->leftEntityAdderThrowLeftEntityGetterMustReturnCollection($collection);
        }

        $isInCollection = false;
        foreach ($collection as $entity) {
            if ($entity === $this->rightEntity) {
                $isInCollection = true;
                break;
            }
        }
        if ($isInCollection === false) {
            $this->leftEntityAdderThrowAdderDoNotAddRightEntity();
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function leftEntityAdderValidateOnlyOneRightEntityIsInCollection()
    {
        $countEntities = 0;
        foreach (call_user_func([ $this->leftEntity, $this->leftEntityGetter ], $this->rightEntity) as $entity) {
            if ($entity === $this->rightEntity) {
                $countEntities++;
            }
        }

        if ($countEntities > 1) {
            $this->leftEntityAdderThrowAdderShouldNotAddSameRightEntityTwice();
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function leftEntityAdderValidateFlush()
    {
        try {
            $this->getManager()->flush();
        } catch (ORMInvalidArgumentException $e) {
            $this->leftEntityAdderThrowAdderOrmInvalidArgumentException($e);
        }

        if (call_user_func([ $this->rightEntity, $this->rightEntityIdGetter ]) === null) {
            $this->leftEntityAdderThrowRightEntityIdIsNull();
        }

        $this->leftEntityAdderAddFlushValidation();

        return $this;
    }

    /**
     * @return $this
     */
    protected function leftEntityAdderValidateRefresh()
    {
        $this->getManager()->refresh($this->leftEntity);
        $this->getManager()->refresh($this->rightEntity);
        $this->leftEntityAdderValidateRightEntityIsInCollection();

        $this->leftEntityAdderAddRefreshValidation();

        return $this;
    }

    /**
     * @throws ReportException
     */
    protected function leftEntityAdderThrowAdderDoesntSetRightEntityProperty()
    {
        $message = $this->getLeftEntityClassName() . '::' . $this->leftEntityAdderMethod . '() does not set ';
        $message .= $this->getRightEntityClassName() . '::$' . $this->getRightEntityPropperty();
        $errorReport = new ErrorReport($message);

        $helpLeftentity = 'As Doctrine use Many side of relations to get informations at update / insert, ';
        $helpLeftentity .= $this->getLeftEntityClassName() . '::' . $this->leftEntityAdderMethod . '() should call ';
        $helpLeftentity .= $this->getRightEntityClassName() . '::' . $this->rightEntitySetter . '($this). Otherwhise, ';
        $helpLeftentity .= $this->getRightEntityClassName() . ' will not be saved with relation to ' . $this->getLeftEntityClassName() . '.';
        $errorReport->addHelp($helpLeftentity);

        $helpRightEntity = $this->getRightEntityClassName() . '::' . $this->rightEntitySetter . '() should set ';
        $helpRightEntity .= $this->getRightEntityClassName() . '::$' . $this->getRightEntityPropperty() . '.';
        $errorReport->addHelp($helpRightEntity);

        $helpRightEntity = $this->getRightEntityClassName() . '::' . $this->rightEntityGetter . '() should return ';
        $helpRightEntity .= $this->getRightEntityClassName() . '::$' . $this->getRightEntityPropperty() . '.';
        $errorReport->addHelp($helpRightEntity);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityAdderMethod);
        $errorReport->addMethodCode($this->rightEntity, $this->rightEntitySetter);
        $errorReport->addMethodCode($this->rightEntity, $this->rightEntityGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param mixed $collection
     * @throws ReportException
     */
    protected function leftEntityAdderThrowLeftEntityGetterMustReturnCollection($collection) {
        $message = $this->getLeftEntityClassName() . '::' . $this->leftEntityGetter . '() ';
        $message .= 'must return an instance of ' . Collection::class . ', ' . gettype($collection) . ' returned.';
        $errorReport = new ErrorReport($message);
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function leftEntityAdderThrowAdderDoNotAddRightEntity()
    {
        $message = $this->getLeftEntityClassName() . '::' . $this->leftEntityAdderMethod . '() ';
        $message .= 'does not add ' . $this->getRightEntityClassName() . '.';
        $errorReport = new ErrorReport($message);

        $help = $this->getLeftEntityClassName() . '::' . $this->leftEntityAdderMethod . '() should add ';
        $help .= $this->getRightEntityClassName() . ' in ' . $this->getLeftEntityClassName() . '::$' . $this->getLeftEntityProperty() . '.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityAdderMethod);
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function leftEntityAdderThrowAdderShouldNotAddSameRightEntityTwice()
    {
        $message = $this->getLeftEntityClassName() . '::' . $this->leftEntityAdderMethod . '() ';
        $message .= 'should not add same ' . $this->getRightEntityClassName() . ' instance twice.';
        $errorReport = new ErrorReport($message);

        $help = $this->getLeftEntityClassName() . '::' . $this->leftEntityAdderMethod . '() should use ';
        $help .= $this->getLeftEntityClassName() . '::$' . $this->getLeftEntityProperty() . '->contains().';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityAdderMethod);
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param ORMInvalidArgumentException $exception
     * @throws ReportException
     */
    protected function leftEntityAdderThrowAdderOrmInvalidArgumentException(ORMInvalidArgumentException $exception)
    {
        $message = 'ORMInvalidArgumentException occured after calling ';
        $message .= $this->getLeftEntityClassName() . '::' . $this->leftEntityAdderMethod . '(), ';
        $message .= 'then ' . get_class($this->getManager()) . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addError($exception->getMessage());
        $this->leftEntityAdderAddLeftEntityPersistError($errorReport);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @throws ReportException
     */
    protected function leftEntityAdderThrowRightEntityIdIsNull()
    {
        $message = $this->getRightEntityClassName() . '::$id is null after calling ';
        $message .= $this->getLeftEntityClassName() . '::' . $this->leftEntityAdderMethod . '(), ';
        $message .= 'then ' . get_class($this->getManager()) . '::flush().';
        $errorReport = new ErrorReport($message);

        $errorReport->addMethodCode($this->rightEntity, $this->rightEntityIdGetter);
        $this->leftEntityAdderAddLeftEntityPersistError($errorReport);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @param ErrorReport $errorReport
     * @return $this
     */
    protected function leftEntityAdderAddLeftEntityPersistError(ErrorReport $errorReport)
    {
        $propertyMetadata = $this
            ->getManager()
            ->getClassMetadata($this->getLeftEntityClassName())
            ->associationMappings[$this->getLeftEntityProperty()];

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
    protected function leftEntityAdderAddOnlyOneRightEntityValidation()
    {
        $message = 'Add only one ' . $this->getRightEntityClassName() . ', even with mutiple calls with same instance.';
        $this->getValidationReport()->addValidation($this->leftEntityAdderValidationName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function leftEntityAdderAddFlushValidation()
    {
        $message = get_class($this->getManager()) . '::flush() ';
        $message .= 'save ' . $this->getLeftEntityClassName() . ' and ' . $this->getRightEntityClassName() . ' correctly.';
        $this->getValidationReport()->addValidation($this->leftEntityAdderValidationName, $message);

        return $this;
    }

    /**
     * @return $this
     */
    protected function leftEntityAdderAddRefreshValidation()
    {
        $message = $this->getRightEntityClassName() . ' is correctly reloaded in ';
        $message .= $this->getLeftEntityClassName() . '::$' . $this->getLeftEntityProperty() . ', ';
        $message .= 'even after calling ' . get_class($this->getManager()) . '::refresh() on all tested entities.';
        $this->getValidationReport()->addValidation($this->leftEntityAdderValidationName, $message);

        return $this;
    }
}
