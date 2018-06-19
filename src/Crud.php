<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud;

use Zend\Form\FormInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use BplCrud\QueryFilter;
use BplCrud\Contract\FormRendererInterface;
use BplCrud\Form\FormRenderer;
use BplCrud\Contract\CrudInterface;

class Crud implements CrudInterface{
    
    /**
     * Fully qualified class name of the object
     * @var string 
     */
    protected $objectClass;

    /**
     * Must have input fields, input-filter and hydrator
     *   -Input filter validates and filters values
     *   -Hydrator converts stored value to field value and vice versa
     * Optionally form can also have object binded to it
     * @var \Zend\Form\FormInterface 
     */
    protected $form;

    /**
     * Used to store and retrieve object from storage
     * @var Doctrine\ORM\EntityManagerInterface
     */
    protected $persistanceManager;

    /**
     * @var \BplCrud\QueryFilter 
     */
    protected $queryFilter;

    /**
     * Object Repository
     * @var \Doctrine\Common\Persistence\ObjectRepository 
     */
    public $objectRepository;
    
    /**
     * Form Renderer
     * Default renderer is plain but custom renderer can be injected to Crud class
     * via __construct(...) and custom renderer must implement \BplCrud\Contract\FormRendererInterface
     * @var \BplCrud\Contract\FormRendererInterface
     */
    public $formRenderer;

    /**
     * Default constructor
     * @param FormInterface $form
     * @param EntityManagerInterface $persistanceManager
     * @param string $objectClass
     */
    public function __construct(FormInterface $form, EntityManagerInterface $persistanceManager, $objectClass = '', FormRendererInterface $formRenderer=NULL) {
        $this->form = $form;
        $this->persistanceManager = $persistanceManager;
        if ($objectClass == '') {
            $objectClass = get_class($this->form->getObject());
        }
        $this->objectClass = $objectClass;
        $this->objectRepository = $this->persistanceManager->getRepository($objectClass);
        
        /**
         * If renderer is not provided; use default renderer
         */
        if($formRenderer==NULL){
            $this->formRenderer = new FormRenderer();
        }else{
            $this->formRenderer = $formRenderer;
        }
    }

    /**
     * Bind object to form
     * @param type $object
     */
    public function bind($object) {
        $this->form->bind($object);
    }

    /**
     * Get object from form
     * @param type $object
     */
    public function getObject() {
        return $this->form->getObject();
    }

    /**
     * Set data from input from
     * @param array $formData
     */
    public function setData($formData) {
        $this->form->setData($formData);
    }

    /**
     * Check if form is valid
     * @return boolean
     */
    public function isValid() {
        return $this->form->isValid();
    }

    /**
     * Extracts object from the form and saves to database
     * @return boolean
     */
    public function create() {
        $ret = false;
        if ($this->isvalid()) {
            $this->persistanceManager->persist($this->form->getObject());
            $this->persistanceManager->flush();
            $ret = true;
        }
        return $ret;
    }

    /**
     * 
     * @param \BplCrud\QueryFilter $queryFilter
     * @param interger $offset
     * @param interger $limit
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function read(QueryFilter $queryFilter, $offset = 0, $limit = 10) {
        $qb = $this->persistanceManager->createQueryBuilder();
        $qb->select('u')->from($this->objectClass, 'u');
        $qb = $queryFilter->getModifiedQueryBuilder($qb);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $query = $qb->getQuery();
        $paginator = new Paginator($query);
        return $paginator;
    }

    /**
     * Extracts object from the form and updates database entry corresponding to that object
     * @return boolean
     */
    public function update() {
        $ret = false;
        if ($this->isvalid()) {
            $this->persistanceManager->merge($this->form->getObject());
            $this->persistanceManager->flush();
            $ret = true;
        }
        return $ret;
    }

    /**
     * Deletes given object from database
     * @param object $object
     * @return boolean
     */
    public function delete($object) {
        $this->persistanceManager->remove($object);
        $this->persistanceManager->flush();
        return true;
    }

    /**
     * Get form element
     * @return Zend\Form\FormInterface
     */
    public function getForm(){
        return $this->form;
    }
    
    /**
     * Returns FQCN of all the entities managed by $persistanceManager
     * @param EntityManagerInterface $persistanceManager
     * @return array
     */
    static public function getAllEntityNames(EntityManagerInterface $persistanceManager){
        $metadata = $persistanceManager->getMetadataFactory()->getAllMetadata();
        $entityNames = [];
        foreach($metadata as $classMeta) {
            $entityNames[] = $classMeta->getName(); // Entity FQCN
        }
        return $entityNames;
    }
    
    /**
     * Displays form using form renderer 
     */
    public function displayForm(){
        $this->formRenderer->displayForm($this->form);
    }
    
}
