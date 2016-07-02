<?php

namespace steevanb\DoctrineMappingValidator\OneToMany\Behavior;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait ValidateLeftEntityPropertyDefaultValueTrait
{
    use PropertiesTrait;

    /**
     * @return $this
     * @throws ReportException
     */
    protected function validateLeftEntityPropertyDefaultValue()
    {
        $collection = call_user_func([ $this->leftEntity, $this->leftEntityGetter ]);
        if ($collection instanceof Collection === false) {
            $this->throwLeftEntityDefaultGetterMustReturnCollection();
        }

        $this->addPassedLeftEntityPropertyDefaultValueTest();

        return $this;
    }

    /**
     * @throws ReportException
     */
    protected function throwLeftEntityDefaultGetterMustReturnCollection()
    {
        $message = $this->leftEntityClass . '::' . $this->leftEntityGetter . '()';
        $message .= ' must return an instance of ' . Collection::class;
        $errorReport = new ErrorReport($message);

        $helpCollection = 'You should call $this->$' . $this->leftEntityProperty;
        $helpCollection .= ' = new ' . ArrayCollection::class . '() in ' . $this->leftEntityClass . '::__construct().';
        $errorReport->addHelp($helpCollection);

        $helpReturn = $this->leftEntityClass . '::' . $this->leftEntityGetter . '() should return ';
        $helpReturn .= $this->leftEntityClass . '::$' . $this->leftEntityProperty . '.';
        $errorReport->addHelp($helpReturn);

        $errorReport->addMethodCode($this->leftEntity, '__construct');
        $errorReport->addMethodCode($this->leftEntity, $this->leftEntityGetter);

        throw new ReportException($this->getReport(), $errorReport);
    }

    /**
     * @return $this
     */
    protected function addPassedLeftEntityPropertyDefaultValueTest()
    {
        $message = $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' is correctly initialized';
        $message .= ' as an instance of ' . Collection::class . '.';
        $this->validationReport->addValidation($this->initializationTestName, $message);

        return $this;
    }
}
