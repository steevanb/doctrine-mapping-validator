<?php

namespace steevanb\DoctrineMappingValidator\Report;

class Report
{
    /** @var \DateTime */
    protected $date;

    /** @var PassedReport[] */
    protected $passed = [];

    /** @var ErrorReport[] */
    protected $errors = [];

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
     * @param PassedReport $passed
     * @return $this
     */
    public function addPassed(PassedReport $passed)
    {
        $this->passed[] = $passed;

        return $this;
    }

    /**
     * @return PassedReport[]
     */
    public function getPassed()
    {
        return $this->passed;
    }
}
