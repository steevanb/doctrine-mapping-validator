<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne;

use steevanb\DoctrineMappingValidator\ManyToOne\Behavior\CreateEntityTrait;
use steevanb\DoctrineMappingValidator\ManyToOne\Behavior\ValidateInverseSideAdderTrait;
use steevanb\DoctrineMappingValidator\ManyToOne\Behavior\ValidateInverseSidePropertyDefaultValueTrait;
use steevanb\DoctrineMappingValidator\ManyToOne\Behavior\ValidateInverseSideSetterTrait;

class DefaultValidatorManyToOne extends AbstractValidatorManyToOne
{
    use CreateEntityTrait;
    use ValidateInverseSidePropertyDefaultValueTrait;
    use ValidateInverseSideAdderTrait;
    use ValidateInverseSideSetterTrait;

    /**
     * @return $this;
     */
    protected function doValidate()
    {
        $direction = $this->isBidirectionnal() ? 'bidirectionnal' : 'unidirectionnal';
        $this->getValidationReport()->setMessage(
            $this->getOwningSideClassName() . '::$' . $this->getOwningSideProperty()
            . ' : ' . $direction . ' manyToOne with ' . $this->getInverseSideClassName()
        );

        if ($this->isBidirectionnal()) {
            $this
                ->validateInverseSidePropertyDefaultValue()
                ->validateInverseSideSetter()
                ->validateInverseSideAdder();
        }

        return $this;
    }
}
