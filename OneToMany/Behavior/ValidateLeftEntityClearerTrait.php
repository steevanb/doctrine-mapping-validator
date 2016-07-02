<?php

namespace steevanb\DoctrineMappingValidator\OneToMany\Behavior;

trait ValidateLeftEntityClearerTrait
{
    use PropertiesTrait;

    /**
     * @return $this
     */
    protected function validateLeftentityClearer()
    {
        $this->clearRightEntities();

        return $this;
    }

}
