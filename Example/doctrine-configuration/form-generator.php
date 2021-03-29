<?php

use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\Laminas\Hydrator\DoctrineObject; 
use BplCrud\Generator\FormGenerator;

$em = require_once 'entity-manager-configuration.php';

$driver = new DatabaseDriver(
        $em->getConnection()->getSchemaManager()
);
$driver->setNamespace('Entity\\Namespace\\');
$em->getConfiguration()->setMetadataDriverImpl($driver);
$classes = $driver->getAllClassNames();

foreach ($classes as $entityName) {
    $formGenerator = new FormGenerator(
            $em,
            $entityName,
            DoctrineObject::class,
            "Form\\Namespace",
            'full path where created forms are to be written'
    );
    $formGenerator->generateForm();
}

