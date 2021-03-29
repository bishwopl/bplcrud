<?php

$em = require_once 'entity-manager-configuration.php';

$serviceCreator = new \BplCrud\Generator\ServiceCreator(
        $em,
        "Entity\\Namespace",
        "Form\\Namespace",
        "Service\\Namespace"
);
$serviceCreator->createServices('full path where created services are to be written');