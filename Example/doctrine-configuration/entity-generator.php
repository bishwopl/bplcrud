<?php

$em = require_once 'entity-manager-configuration.php';

$driver = new \Doctrine\ORM\Mapping\Driver\DatabaseDriver(
                $em->getConnection()->getSchemaManager()
);
$driver->setNamespace('Entity\\Namespace\\');
$em->getConfiguration()->setMetadataDriverImpl($driver);
$cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory($em);
$cmf->setEntityManager($em);
$classes = $driver->getAllClassNames();
$metadata = $cmf->getAllMetadata();
$generator = new Doctrine\ORM\Tools\EntityGenerator();
$generator->setUpdateEntityIfExists(true);
$generator->setGenerateStubMethods(true);
$generator->setGenerateAnnotations(true);
$generator->generate($metadata, 'full path where created entities are to be written');
print 'Done!';
