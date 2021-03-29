<?php

$em = require_once 'entity-manager-configuration.php';

$form = new \MyCrud\Form\MyEntity\MyEntityForm($em, 'roleForm', [], 2);
$mapper = new \BplCrud\Mapper\DoctrineMapper($em, MyCrud\Entity\MyEntity::class);
$crud = new \BplCrud\Crud($mapper, $form, \MyCrud\Entity\MyEntity::class);
$result = $crud->read([
    "field-name" => "value"
]);

foreach ($result as $r) {
    var_dump($r);
}

