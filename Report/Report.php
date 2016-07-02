<?php

namespace steevanb\DoctrineMappingValidator\Report;

class Report
{
    /** @var \DateTime */
    protected $date;

    /** @var ErrorReport[] */
    protected $errors = [];

    /** @var ValidationReport[] */
    protected $validations = [];

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param ErrorReport $error
     * @return $this
     */
    public function addError(ErrorReport $error)
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * @return ErrorReport[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param ValidationReport $report
     * @return $this
     */
    public function addValidation(ValidationReport $report)
    {
        $this->validations[] = $report;

        return $this;
    }

    /**
     * @return ValidationReport[]
     */
    public function getValidations()
    {
        return $this->validations;
    }
}
