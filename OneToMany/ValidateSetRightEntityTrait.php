<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use Doctrine\Common\Collections\ArrayCollection;
use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\ReportException;

trait ValidateSetRightEntityTrait
{
    use PropertiesTrait;

    /**
     * @return $this
     * @throws ReportException
     */
    protected function validateSetRightEntities()
    {
        $this->assertSetRightEntities();

        return $this;
    }

    /**
     * @return $this
     * @throws ReportException
     */
    protected function assertSetRightEntities()
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
        ) {
            $this->throwLeftEntitySetterDoesntSetProperty();
        }

        if (
            call_user_func([ $this->rightEntity, $this->rightEntityGetter ]) !== $this->leftEntity
            || call_user_func([ $this->rightEntity2, $this->rightEntityGetter ]) !== $this->leftEntity
        ) {
            $this->throwLeftEntitySetterDoesntSetProperty();
        }

        $this->addPassedSetLeftEntityProperty();

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

        throw new ReportException($this->report, $errorReport);
    }

    /**
     * @return $this
     */
    protected function addPassedSetLeftEntityProperty()
    {
        $message = 'Set ' . $this->leftEntityClass . '::$' . $this->leftEntityProperty . ' correctly, ';
        $message .= 'even with multiple calls.';
        $this->passedReport->addTest($this->leftEntitySetterTestName, $message);

        return $this;
    }
}
