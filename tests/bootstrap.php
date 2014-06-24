<?php

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->addPsr4('Anonymizer\\', __DIR__.'/Anonymizer');

date_default_timezone_set('UTC');
