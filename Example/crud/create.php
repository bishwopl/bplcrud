<?php

$em = require_once 'entity-manager-configuration.php';

$crud = new \MyCrud\Service\MyEntityService($em);
$crud->setFormAttribute('action','create.php');
$crud->displayForm();

if(isset($_POST['myEntity'])){
    $crud->setData($_POST);
    if($crud->isFormValid()){
        $obj = $crud->getObject();
        $crud->create($obj);
        echo 'Created successfully';
    }else{
        var_dump($crud->getErrorMessages());
    }
}
