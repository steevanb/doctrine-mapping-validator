<?php

namespace steevanb\DoctrineMappingValidator\OneToMany;

use steevanb\DoctrineMappingValidator\Report\Report;
use steevanb\DoctrineMappingValidator\Report\ReportException;

class AbstractValidatorOneToMany implements ValidatorOneToManyInterface
{
    use ValidatorOneToManyTrait;
    use AddRightObjectOneToManyTrait;

    /**
     * @return Report
     */
    public function validate()
    {
        try {
            $this->validateAddRightObject();
        } catch (ReportException $e) {}

        return $this->getReport();
    }
}
