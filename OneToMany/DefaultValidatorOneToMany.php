<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use steevanb\DoctrineMappingValidator\OneToMany\Behavior\InitTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateLeftEntityAdderTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateLeftEntityClearerTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateLeftEntityRemoverTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateLeftEntitySetterTrait;

class DefaultValidatorOneToMany extends AbstractValidatorOneToMany
{
    use ValidateLeftEntityAdderTrait;
//    use ValidateLeftEntityRemoverTrait;
//    use ValidateLeftEntitySetterTrait;
//    use ValidateLeftEntityClearerTrait;

    /**
     * @return $this;
     */
    protected function doValidate()
    {
        $message = $this->getLeftEntityClassName() . '::$' . $this->getLeftEntityProperty() . ' : ';
        $message .= 'oneToMany with ' . $this->getRightEntityClassName();
        $this->getValidationReport()->setMessage($message);

        $this
            ->validateLeftEntityAdder();
//            ->validateLeftEntityRemover();
//            ->validateLeftEntitySetter()
//            ->validateLeftentityClearer();

        return $this;
    }
}
