<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use steevanb\DoctrineMappingValidator\OneToMany\Behavior\InitTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateLeftEntityAdderTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateLeftEntityClearerTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateLeftEntityPropertyDefaultValueTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateLeftEntityRemoverTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateLeftEntitySetterTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateMethodsExistsTrait;
use steevanb\DoctrineMappingValidator\OneToMany\Behavior\ValidateMethodsParametersTrait;

class DefaultValidatorOneToMany extends AbstractValidatorOneToMany
{
    use InitTrait;
    use ValidateMethodsExistsTrait;
    use ValidateMethodsParametersTrait;
    use ValidateLeftEntityPropertyDefaultValueTrait;
    use ValidateLeftEntityAdderTrait;
    use ValidateLeftEntityRemoverTrait;
    use ValidateLeftEntitySetterTrait;
    use ValidateLeftEntityClearerTrait;

    /**
     * @return $this;
     */
    protected function callValidates() {
        $this
            ->validateMethodsExists()
            ->validateMethodsParameters()
            ->validateLeftEntityPropertyDefaultValue()
            ->validateLeftEntityAdder()
            ->validateLeftEntityRemover()
            ->validateLeftEntitySetter()
            ->validateLeftentityClearer();

        return $this;
    }
}
