<?php

$em = require_once 'entity-manager-configuration.php';

$crud = new \MyCrud\Service\MyEntityService($em);
$result = $crud->read([
    "field-name" => "value"
]);

foreach($result as $r){
    var_dump($r);
}

