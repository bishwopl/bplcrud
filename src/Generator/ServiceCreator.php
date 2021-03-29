<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud\Generator;

use BplCrud\Crud;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

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

    public function __construct(
            EntityManagerInterface $persistanceManager,
            $entityNamespace,
            $formNamespace,
            $serviceNamespace
    ) {
        $this->persistanceManager = $persistanceManager;
        $this->entityNamespace = $entityNamespace;
        $this->formNamespace = $formNamespace;
        $this->serviceNamespace = $serviceNamespace;
    }

    public function createServices($savePath) {
        $factory = new ClassMetadataFactory();
        $factory->setEntityManager($this->persistanceManager);
        $entityNames = $this->getAllEntityNames();
        foreach ($entityNames as $fcqn) {
            $dummy = explode('\\', $fcqn);
            $entityName = $dummy[sizeof($dummy) - 1];

            $body = '$form = new ' . $entityName . 'Form($persistanceManager, "' . lcfirst($entityName) . '");'. PHP_EOL
                    . '$mapper = new \BplCrud\Mapper\DoctrineMapper($persistanceManager, '.$entityName.'::class);' . PHP_EOL
                    . 'parent::__construct($mapper, $form, ' . $entityName . '::class);';

            $serviceGenerator = new ClassGenerator(
                    $entityName . 'Service', $this->serviceNamespace, null, Crud::class, [], [], [], null
            );
            $serviceGenerator->addUse($this->formNamespace . '\\' . $entityName . '\\' . $entityName . 'Form');
            $serviceGenerator->addUse($this->entityNamespace . '\\' . $entityName);
            
            $method = new MethodGenerator(
                    "__construct", [['name' => 'persistanceManager', 'type' => EntityManagerInterface::class]], MethodGenerator::FLAG_PUBLIC, $body
            );

            $serviceGenerator->addMethodFromGenerator($method);
            $contents = $serviceGenerator->generate();

            file_put_contents($savePath . '/' . $entityName . 'Service.php', '<?php ' . PHP_EOL . $contents);
        }
    }
    
    protected function getAllEntityNames() {
        $metadata = $this->persistanceManager->getMetadataFactory()->getAllMetadata();
        $entityNames = [];
        foreach ($metadata as $classMeta) {
            $entityNames[] = $classMeta->getName(); // Entity FQCN
        }
        return $entityNames;
    }

}
