<?php

namespace steevanb\DoctrineMappingValidator\Report;

class ReportException extends \Exception
{
    /**
     * @param Report $report
     * @param ErrorReport $errorReport
     */
    public function __construct(Report $report, ErrorReport $errorReport)
    {
        $report->addError($errorReport);

        parent::__construct($errorReport->getMessage());
    }
}
