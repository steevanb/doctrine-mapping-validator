<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Version;
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

    /** @return array */
    abstract protected function getInverseSideSetterMethodValidation();

    /** @return string */
    abstract protected function getInverseSideGetter();

    /** @return array */
    abstract protected function getInverseSideGetterMethodValidation();

    /** @return string */
    abstract protected function getInverseSideProperty();

    /** @return string */
    abstract protected function getInverseSideAdder();

    /** @return array */
    abstract protected function getInverseSideAdderMethodValidation();

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

    /** @return array */
    abstract protected function getOwningSideSetterMethodValidation();

    /** @return object */
    abstract protected function getOwningSideGetter();

    /** @return array */
    abstract protected function getOwningSideGetterMethodValidation();

    /** @return string */
    abstract protected function getOwningSideClassName();

    /** @return string */
    abstract protected function getOwningSideProperty();

    /** @return string */
    abstract protected function getOwningSideIdGetter();

    /** @return array */
    abstract protected function getOwningSideIdGetterMethodValidation();

    /** @return object */
    abstract protected function createOwningSideEntity();

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
     * @throws ReportException
     */
    protected function validateInverseSideSetter()
    {
        $this
            ->inverseSideSetterInit()
            ->inverseSideSetterValidateOwningSideMethods()
            ->inverseSideSetterValidateInverseSideMethods()
            ->inverseSideSetterValidate()
            ->inverseSideSetterValidateFlush()
            ->inverseSideSetterValidateReloadEntities();

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideSetterInit()
    {
        $this->inverseSideSetterTestName =
            $this->getInverseSideClassName() . '::' . $this->getInverseSideSetter() . '()';

        // Doctrine won't allow us to call add(), remove() then add() with same entity
        // as first test is just PHP implementation, it call add(), clear(), add(), set() etc
        // so, just don't persist entities at this stage
        $this->inverseSideSetterInitEntities(false);

        return $this;
    }

    /**
     * @param bool $persist
     * @return $this
     */
    protected function inverseSideSetterInitEntities($persist = true)
    {
        $this->setInverseSideEntity($this->createInverseSideEntity());

        $this->setOwningSideEntity($this->createOwningSideEntity());
        $this->owningSideEntity2 = $this->createOwningSideEntity();

        if ($persist) {
            $this->getManager()->persist($this->getOwningSideEntity());
            $this->getManager()->persist($this->owningSideEntity2);
        }

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
                $this->getInverseSideSetterMethodValidation(),
                $this->getInverseSideGetterMethodValidation()
            ],
            $this->inverseSideSetterTestName
        );

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideSetterValidateOwningSideMethods()
    {
        $this->validateMethods(
            $this->getOwningSideEntity(),
            [
                $this->getOwningSideIdGetterMethodValidation(),
                $this->getOwningSideSetterMethodValidation(),
                $this->getOwningSideGetterMethodValidation()
            ],
            $this->inverseSideSetterTestName
        );

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function inverseSideSetterValidate()
    {
        call_user_func([$this->getOwningSideEntity(), $this->getOwningSideSetter()], null);
        call_user_func([$this->owningSideEntity2, $this->getOwningSideSetter()], null);

        $owningSideEntities = new ArrayCollection([$this->getOwningSideEntity(), $this->owningSideEntity2]);
        call_user_func([$this->getInverseSideEntity(), $this->getInverseSideSetter()], $owningSideEntities);
        // try to call setter 2 times, to be sure it clear owningSideEntities, add, then reset Collection indexes
        call_user_func([$this->getInverseSideEntity(), $this->getInverseSideSetter()], $owningSideEntities);

        /** @var Collection $settedOwningSideEntities */
        $settedOwningSideEntities = call_user_func([$this->getInverseSideEntity(), $this->getInverseSideGetter()]);

        if (
            (
                // PersistentCollection doesn't reset Collection keys when Collection is empty before 2.5.5
                $settedOwningSideEntities instanceof PersistentCollection === false
                || version_compare(Version::VERSION, '2.5.5') >= 0
            )
            && count($settedOwningSideEntities) === 2
            && (
                isset($settedOwningSideEntities[0]) === false
                || isset($settedOwningSideEntities[1]) === false
            )
        ) {
            $this->inverseSideSetterThrowDoesntClearCollection();
        }

        if (
            count($settedOwningSideEntities) !== 2
            || $settedOwningSideEntities->first() !== $this->getOwningSideEntity()
            || $settedOwningSideEntities->next() !== $this->owningSideEntity2
            || call_user_func([$this->getOwningSideEntity(), $this->getOwningSideGetter()]) !== $this->getInverseSideEntity()
            || call_user_func([$this->owningSideEntity2, $this->getOwningSideGetter()]) !== $this->getInverseSideEntity()
        ) {
            $this->inverseSideSetterThrowDoesntSetProperty();
        }

        call_user_func([$this->getInverseSideEntity(), $this->getInverseSideClearer()]);
        $this->inverseSideSetterAddSetPropertyValidation();

        return $this;
    }

    /**
     * @return $this
     */
    protected function inverseSideSetterValidateFlush()
    {
        $this->inverseSideSetterInitEntities();
        $owningSideEntities = new ArrayCollection([$this->getOwningSideEntity(), $this->owningSideEntity2]);
        call_user_func([$this->getInverseSideEntity(), $this->getInverseSideSetter()], $owningSideEntities);

        try {
            $this->getManager()->flush();
        } catch (ORMInvalidArgumentException $e) {
            $this->inverseSideSetterThrowOrmInvalidArgumentException($e);
        }

        if (
            call_user_func([$this->getOwningSideEntity(), $this->getOwningSideIdGetter()]) === null
            || call_user_func([$this->owningSideEntity2, $this->getOwningSideIdGetter()]) === null
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
        $mappedBy = call_user_func([$this->getOwningSideEntity(), $this->getOwningSideGetter()]);
        if ($mappedBy !== $this->getInverseSideEntity()) {
            $this->inverseSideSetterThrowDoesntSetOwningSideProperty();
        }
        $mappedBy = call_user_func([$this->owningSideEntity2, $this->getOwningSideGetter()]);
        if ($mappedBy !== $this->getInverseSideEntity()) {
            $this->inverseSideSetterThrowDoesntSetOwningSideProperty();
        }

        /** @var Collection $collection */
        $collection = call_user_func([$this->getInverseSideEntity(), $this->getInverseSideGetter()]);
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

        $errorReport->addMethodCode($this->getInverseSideClassName(), $this->getInverseSideSetter());
        $errorReport->addMethodCode($this->getInverseSideClassName(), $this->getInverseSideGetter());

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
        $errorReport->addMethodCode($this->getInverseSideClassName(), $this->getInverseSideGetter());

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

        $errorReport->addMethodCode($this->getInverseSideClassName(), $this->getInverseSideSetter());
        $errorReport->addMethodCode($this->getOwningSideClassName(), $this->getOwningSideSetter());
        $errorReport->addMethodCode($this->getOwningSideClassName(), $this->getOwningSideGetter());

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

        $errorReport->addMethodCode($this->getOwningSideClassName(), $this->getOwningSideIdGetter());
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
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideClearer() . '() do not reset ';
        $message .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . ' keys.';
        $errorReport = new ErrorReport($message);

        $help = $this->getInverseSideClassName() . '::' . $this->getInverseSideClearer() . '() should call ';
        $help .= '$this->' . $this->getInverseSideProperty() . '->clear() at then end ';
        $help .= 'to reset Collection keys.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->getInverseSideClassName(), $this->getInverseSideClearer());

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

        $help = 'This method should call $this->' . $this->getInverseSideClearer() . '(), ';
        $help .= $this->getInverseSideAdder() . '() for each ' . $this->getOwningSideClassName() . ' passed ';
        $help .= 'then ' . $this->getInverseSideProperty() . '->clear() to reset Collection keys.';
        $errorReport->addHelp($help);

        $errorReport->addMethodCode($this->getInverseSideClassName(), $this->getInverseSideSetter());

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
