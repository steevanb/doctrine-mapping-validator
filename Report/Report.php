<?php

namespace steevanb\DoctrineMappingValidator\Report;

class Report
{
    /** @var \DateTime */
    protected $date;

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
}
