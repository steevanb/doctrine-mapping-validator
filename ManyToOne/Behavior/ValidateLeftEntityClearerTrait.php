<?php

namespace steevanb\DoctrineMappingValidator\ManyToOne\Behavior;

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
