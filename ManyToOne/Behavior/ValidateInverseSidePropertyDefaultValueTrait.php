<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;

trait ValidateInverseSidePropertyDefaultValueTrait
{
    /**
     * @param object $entity
     * @return $this
     */
    abstract protected function setInverseSideEntity($entity);

    /** @return object */
    abstract protected function createInverseSideEntity();

    /** @return object */
    abstract protected function getInverseSideEntity();

    /** @return string */
    abstract protected function getInverseSideGetter();

    /** @return string */
    abstract protected function getInverseSideClassName();

    /** @return string */
    abstract protected function getInverseSideProperty();

    /** @return Report */
    abstract protected function getReport();

    /** @return ValidationReport */
    abstract protected function getValidationReport();

    /**
     * @return $this
     * @throws ReportException
     */
    protected function validateInverseSidePropertyDefaultValue()
    {
        $this->setInverseSideEntity($this->createInverseSideEntity());
        $collection = call_user_func([$this->getInverseSideEntity(), $this->getInverseSideGetter()]);
        if ($collection instanceof Collection === false) {
            $this->throwInverseSideDefaultGetterMustReturnCollection();
        }

        $this->addInverseSideGetterDefaultValueValidation();

        return $this;
    }

    /**
     * @throws ReportException
     */
    protected function throwInverseSideDefaultGetterMustReturnCollection()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideGetter() . '()';
        $message .= ' must return an instance of ' . Collection::class;
        $errorReport = new ErrorReport($message);

        $helpCollection = 'You should call $this->$' . $this->getInverseSideProperty();
        $helpCollection .= ' = new ' . ArrayCollection::class . '() in ';
        $helpCollection .= $this->getInverseSideClassName() . '::__construct().';
        $errorReport->addHelp($helpCollection);

        $helpReturn = $this->getInverseSideClassName() . '::' . $this->getInverseSideGetter() . '() should return ';
        $helpReturn .= $this->getInverseSideClassName() . '::$' . $this->getInverseSideProperty() . '.';
        $errorReport->addHelp($helpReturn);

        $errorReport->addMethodCode($this->getInverseSideClassName(), '__construct');
        $errorReport->addMethodCode($this->getInverseSideClassName(), $this->getInverseSideGetter());

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @return $this
     */
    protected function addInverseSideGetterDefaultValueValidation()
    {
        $message = $this->getInverseSideClassName() . '::' . $this->getInverseSideGetter() . '() ';
        $message .= 'return an instance of ' . Collection::class . '.';
        $this->getValidationReport()->addValidation(
            $this->getInverseSideClassName() . '::' . $this->getInverseSideGetter() . '()',
            $message
        );

        return $this;
    }
}
