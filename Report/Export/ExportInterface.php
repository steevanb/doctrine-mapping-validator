<?php

namespace steevanb\DoctrineMappingValidator\Report\Export;

use steevanb\DoctrineMappingValidator\Report\Report;

interface ExportInterface
{
    /**
     * @param Report $report
     * @return string
     */
    public function export(Report $report);
}
