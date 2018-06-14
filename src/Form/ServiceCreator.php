<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace Admission;

use BplCrud\Crud;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Doctrine\ORM\EntityManagerInterface;

class ServiceCreator {

    /**
     *
     * @var \Doctrine\ORM\EntityManagerInterface 
     */
    private $persistanceManager;
    
    /**
     *
     * @var string 
     */
    private $entityNamespace;
    
    /**
     *
     * @var string 
     */
    private $formNamespace;
    
    /**
     *
     * @var string 
     */
    private $serviceNamespace;
    
    /**
     *
     * @var string 
     */
    private $formRendererClassName;

    public function __construct(EntityManagerInterface $persistanceManager, $entityNamespace, $formNamespace, $serviceNamespace, $formRendererClassName) {
        $this->persistanceManager = $persistanceManager;
        $this->entityNamespace = $entityNamespace;
        $this->formNamespace = $formNamespace;
        $this->serviceNamespace = $serviceNamespace;
        $this->formRendererClassName = $formRendererClassName;
    }

    public function createdServices($savePath) {
        $entityNames = Crud::getAllEntityNames($this->persistanceManager);
        foreach ($entityNames as $fcqn) {
            $dummy = explode('\\', $fcqn);
            $entityName = $dummy[sizeof($dummy) - 1];

            $body = '$formRenderer = new '.$this->formRendererClassName.'(); ' . PHP_EOL
                . '$form = new ' . $entityName . 'Form($persistanceManager, "' . lcfirst($entityName) . '");' . PHP_EOL
                . 'parent::__construct($form, $persistanceManager, ' . $entityName . '::class, $formRenderer);';

            $serviceGenerator = new ClassGenerator(
                $entityName . 'Service', $this->serviceNamespace, null, Crud::class, [], [], [], null
            );
            $serviceGenerator->addUse($this->formNamespace.'\\' . $entityName . '\\' . $entityName . 'Form');
            $serviceGenerator->addUse($this->entityNamespace.'\\' . $entityName);
            $serviceGenerator->addUse($this->formRendererClassName);
            $method = new MethodGenerator(
                "__construct", [['name' => 'persistanceManager', 'type' => EntityManagerInterface::class]], MethodGenerator::FLAG_PUBLIC, $body
            );

            $serviceGenerator->addMethodFromGenerator($method);
            $contents = $serviceGenerator->generate();

            file_put_contents($savePath . '/' . $entityName . 'Service.php', '<?php ' . PHP_EOL . $contents);
        }
    }

}