<?php

$em = require_once 'entity-manager-configuration.php';

$crud = new \MyCrud\Service\MyEntityService($em);
$result = $crud->read([
    "field-name" => "value"
]);

foreach($result as $r){
    $crud->bind($r);
    //var_dump($r);
}

if(isset($_POST['myEntity'])){
    $crud->setData($_POST);
    if($crud->isFormValid()){
        $obj = $crud->getObject();
        $crud->update($obj);
    }else{
        var_dump($crud->getErrorMessages());
    }
}

$crud->displayForm();
