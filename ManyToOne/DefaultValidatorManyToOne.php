<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne;

use steevanb\DoctrineMappingValidator\ManyToOne\Behavior\ValidateInverseSideAdderTrait;

class DefaultValidatorManyToOne extends AbstractValidatorManyToOne
{
    use ValidateInverseSideAdderTrait;

    /**
     * @return $this;
     */
    protected function doValidate()
    {
        $message = $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty() . ' : ';
        $message .= 'manyToOne with ' . $this->getInverseSideClassName();
        $this->getValidationReport()->setMessage($message);

        $this
            ->validateInverseSideAdder();

        return $this;
    }
}
