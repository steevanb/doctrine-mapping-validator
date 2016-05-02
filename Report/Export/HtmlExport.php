<?php

namespace steevanb\DoctrineMappingValidator\Report\Export;

use steevanb\DoctrineMappingValidator\Report\ErrorReport;
use steevanb\DoctrineMappingValidator\Report\Report;

class HtmlExport implements ExportInterface
{
    /**
     * @param Report $report
     * @return string
     */
    public function export(Report $report)
    {
        $html = null;
        $this
            ->writeHeader($report, $html)
            ->writeMenu($report, $html)
            ->writeErrors($report, $html)
            ->writeFooter($report, $html);

        return $html;
    }

    /**
     * @param Report $report
     * @param string $html
     * @return $this
     */
    protected function writeHeader(Report $report, &$html)
    {
        $html .= '
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="utf-8">
                    <meta http-equiv="X-UA-Compatible" content="IE=edge">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Doctrine Mapping Validator ' . $report->getDate()->format('Y-m-d H:i:s') . '</title>
                    <link
                        rel="stylesheet"
                        href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"
                        integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7"
                        crossorigin="anonymous"
                    />
                    <style type="text/css">
                    body {
                        margin: 20px;
                        cursor: default;
                    }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="row">
        ';

        return $this;
    }

    /**
     * @param Report $report
     * @param string $html
     * @return $this
     */
    protected function writeMenu(Report $report, &$html)
    {
        $html .= '
                            <div class="col-lg-3">
                                <ul class="nav nav-pills nav-stacked">
                                    <li><a>Menu 1</a></li>
                                    <li><a>Menu 2</a></li>
                                </ul>
                            </div>
        ';

        return $this;
    }

    protected function writeErrors(Report $report, &$html)
    {
        $html .= '
                            <div class="col-lg-9">
        ';

        foreach ($report->getErrors() as $error) {
            $html .= '
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="panel panel-danger">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">' . $error->getMessage() . '</h3>
                                            </div>
                                            <div class="panel-body">
                                                <h4>
                                                    <i class="glyphicon glyphicon-alert"></i>
                                                    Errors
                                                </h4>
            ';
            $html .= implode('<br />', $error->getErrors());
            $html .= '
                                                <h4>
                                                    <i class="glyphicon glyphicon-info-sign"></i>
                                                    Helps
                                                </h4>
            ';
            $html .= implode('<br />', $error->getHelps());

            if (count($error->getFiles()) > 0) {
                $html .= '
                                                <h4>Files</h4>
                                                <ul>
                ';
                foreach ($error->getFiles() as $file) {
                    $html .= '
                                                    <li>' . $file . '</li>
                    ';
                }
                $html .= '
                                                </ul>
                ';
            }

            if (count($error->getCodes()) > 0) {
                $html .= '
                                                <h4>Codes</h4>
                ';
                foreach ($error->getCodes() as $code) {
                    $html .= '
                                                    <pre>' . $code['code'] . '</pre>
                    ';
                }
            }

            $html .= '

                                            </div>
                                        </div>
                                    </div>
                                </div>
            ';
        }

        $html .= '
                            </div>
        ';

        return $this;
    }

    protected function writeErrorFiles(ErrorReport $error, &$html)
    {
        if (count($error->getFiles()) > 0) {
            $html .= '
                                                <h4>Files</h4>
                                                <ul>
                ';
            foreach ($error->getFiles() as $file) {
                $html .= '
                                                    <li>' . $file . '</li>
                    ';
            }
            $html .= '
                                                </ul>
                ';
        }
    }

    /**
     * @param Report $report
     * @param string $html
     * @return $this
     */
    protected function writeFooter(Report $report, &$html)
    {
        $html .= '
                        </div>
                    </div>
                </body>
            </html>
        ';

        return $this;
    }
}
