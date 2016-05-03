<?php
/** @var steevanb\DoctrineMappingValidator\Report\Report $report */
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Doctrine Mapping Validator <?php echo $report->getDate()->format('Y-m-d H:i:s') ?></title>
        <link
            rel="stylesheet"
            href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"
            integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7"
            crossorigin="anonymous"
        />
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.3.0/styles/default.min.css">
        <style type="text/css">
            body {
                margin: 20px;
                cursor: default;
            }

            pre.code {
                border: none;
                background: none;
                padding: 0px;
                margin-bottom: 0px;
            }
            code {
                padding: 0px !important;
                background: none !important;
            }
            .nav {
                background-color: #EEEEEE;
                border-radius: 4px;
            }
            .nav > li > a:focus, .nav > li > a:hover {
                background-color: #dddddd;
            }
            .nav li {
                cursor: pointer;
            }
        </style>
    </head>
    <body>
            <div class="row">
                <div class="col-lg-2">
                    <ul class="nav nav-pills nav-stacked">
                        <li class="active"><a>Menu 1</a></li>
                        <li><a>Menu 2</a></li>
                    </ul>
                </div>
                <div class="col-lg-10">
                    <?php foreach ($report->getErrors() as $error) { ?>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="panel panel-danger">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">
                                            <i class="glyphicon glyphicon-chevron-right"></i>
                                            <?php echo $error->getMessage() ?>
                                        </h3>
                                    </div>
                                    <div class="panel-body">
                                        <?php if (count($error->getErrors()) > 0) { ?>
                                            <h4><i class="glyphicon glyphicon-alert"></i> Errors</h4>
                                            <ul>
                                                <?php foreach ($error->getErrors() as $error) { ?>
                                                    <li><?php echo $error ?></li>
                                                <?php } ?>
                                            </ul>
                                        <?php } ?>

                                        <?php if (count($error->getHelps()) > 0) { ?>
                                            <h4><i class="glyphicon glyphicon-question-sign"></i> Helps</h4>
                                            <ul>
                                                <?php foreach ($error->getHelps() as $help) { ?>
                                                    <li><?php echo $help ?></li>
                                                <?php } ?>
                                            </ul>
                                        <?php } ?>

                                        <?php if (count($error->getFiles()) > 0) { ?>
                                            <h4><i class="glyphicon glyphicon-file"></i> Files</h4>
                                            <ul>
                                                <?php foreach ($error->getFiles() as $file) { ?>
                                                    <li><?php echo $file ?></li>
                                                <?php } ?>
                                            </ul>
                                        <?php } ?>

                                        <?php if (count($error->getCodes()) > 0) { ?>
                                            <h4><i class="glyphicon glyphicon-align-left"></i> Codes</h4>
                                            <?php foreach ($error->getCodes() as $code) { ?>
                                                <div class="panel panel-default">
                                                    <div class="panel-heading">
                                                        <i class="glyphicon glyphicon-file"></i>
                                                        <?php echo $code['file'] ?>, line <?php echo $code['line'] ?>
                                                    </div>
                                                    <div class="panel-body">
                                                        <pre class="code"><code class="php"><?php echo $code['code'] ?></code></pre>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

        <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.3.0/highlight.min.js"></script>
        <script type="text/javascript">
            hljs.initHighlightingOnLoad();
        </script>
    </body>
</html>
