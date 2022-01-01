<?php

$em = require_once 'entity-manager-configuration.php';

$mapper = new \BplCrud\Mapper\DoctrineMapper($em, \MyCrud\Entity\MyEntity::class);
$result = $mapper->findBy([
    "field-name" => "value"
]);
foreach($result as $r){
    var_dump($r);
}