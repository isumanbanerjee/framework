<?php

use Action\EnvFileParser;

require_once __DIR__ . '/resources/vendor/autoload.php';
$parser = new EnvFileParser();
$parser->processEnvFiles();