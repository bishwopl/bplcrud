<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;

$em = require_once 'entity-manager-configuration.php';

return ConsoleRunner::createHelperSet($em);