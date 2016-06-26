<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

class DefaultValidatorOneToMany extends AbstractValidatorOneToMany
{
    use InitTrait;
    use ValidateMethodsExistsTrait;
    use ValidateMethodsParametersTrait;
    use ValidateLeftEntityDefaultValueTrait;
    use ValidateAddRightEntityTrait;
    use ValidateRemoveRightEntityTrait;
    use ValidateSetRightEntityTrait;

    /**
     * @return $this;
     */
    protected function callValidates() {
        $this
            ->validateMethodsExists()
            ->validateMethodsParameters()
            ->validateLeftEntityDefaultValue()
            ->validateAddRightEntity()
            ->validateRemoveRightEntity()
            ->validateSetRightEntities();

        return $this;
    }
}
