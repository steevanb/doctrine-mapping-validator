<?php

use steevanb\PhpCodeSniffs\Steevanb\Sniffs\Uses\GroupUsesSniff;

GroupUsesSniff::addSymfonyPrefixes();

$localPath = __DIR__ . '/phpcs.bootstrap.local.php';
if (file_exists($localPath)) {
    require $localPath;
}
