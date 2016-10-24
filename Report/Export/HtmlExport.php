<?php

namespace steevanb\DoctrineMappingValidator\Report\Export;

use steevanb\DoctrineMappingValidator\Report\Report;

class HtmlExport implements ExportInterface
{
    /** @var string|null */
    protected $template;

    /**
     * @param string|null $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @param Report $report
     * @return string
     */
    public function export(Report $report)
    {
        ob_start();
        require ($this->template === null)
            ? __DIR__ . DIRECTORY_SEPARATOR . 'HtmlTemplateExport.php'
            : $this->template;

        return ob_get_clean();
    }
}
