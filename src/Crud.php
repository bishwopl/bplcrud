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

class Crud implements CrudInterface {

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
    public function __construct(FormInterface $form, EntityManagerInterface $persistanceManager, $objectClass = '', FormRendererInterface $formRenderer = NULL) {
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
        if ($formRenderer == NULL) {
            $this->formRenderer = new FormRenderer();
        } else {
            $this->formRenderer = $formRenderer;
        }
    }

    /**
     * @return string
     */
    public function getObjectClass(){
        return $this->objectClass;
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
     * Read data from table
     * Order by is of type ["columnName1"=>"ASC/DESC", "columnName2"=>"ASC/DESC"]
     * 
     * @param \BplCrud\QueryFilter $queryFilter
     * @param interger $offset
     * @param interger $limit
     * @param array $orderBy
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function read(QueryFilter $queryFilter, $offset = 0, $limit = 10, $orderBy = []) {
        $qb = $this->persistanceManager->createQueryBuilder();
        $qb->select('u')->from($this->objectClass, 'u');
        $qb = $queryFilter->getModifiedQueryBuilder($qb);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy('u.'.$sort, $order);
        }

        $query = $qb->getQuery();

        $paginator = new Paginator($query);
        return $paginator;
    }

    /**
     * Return total count of records in a table marching $queryFilter
     * @param \BplCrud\QueryFilter $queryFilter
     * @return integer
     */
    public function getTotalRecordCount(QueryFilter $queryFilter) {
        $qb = $this->persistanceManager->createQueryBuilder();
        $qb->select('count(u)')->from($this->objectClass, 'u');
        $qb = $queryFilter->getModifiedQueryBuilder($qb);
        $count = $qb->getQuery()->getSingleScalarResult();
        return $count;
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
    public function getForm() {
        return $this->form;
    }

    /**
     * Displays form using form renderer 
     */
    public function displayForm() {
        $this->formRenderer->displayForm($this->form);
    }

    public function readAsArray(QueryFilter $queryFilter, $offset = 0, $limit = 10) {
        $ret = [];
        $data = $this->read($queryFilter, $offset, $limit);
        foreach ($data as $d) {
            $ret[] = get_object_vars($d);
        }
        return $ret;
    }
    
    /**
     * Import data from csv uses header row as field names
     * @param string $absFilePath
     * @param string $keyFieldName
     * @param string $fieldSetName
     * @return array
     * @throws \Exception
     */
    public function importFromCSV($absFilePath, $keyFieldName, $baseFieldSetName='', $updateIfFound=true, $ignoreErrors=true){
        $ret = [
            'result' => false,
            'messages' => [],
            'errors' =>[],
            'rowsInserted' => 0,
            'rowsUpdated' => 0
        ];
        $totalRecords = 0;
        $formData = [];
        
        $validator = new \Zend\Validator\File\Extension(["extention"=>'csv','case'=>false]);
        if(!$validator->isValid($absFilePath)){
            throw new \Exception("File must exist and be of .csv type.");
        }
        $fileHandle = fopen($absFilePath, 'r');
        $fieldNames = fgetcsv($fileHandle, 99999, ",");
        if(!in_array($keyFieldName, $fieldNames)){
            throw new \Exception($keyFieldName ." must be present and cannot be empty");
        }
        
        /**
         * Create associative array of data
         */
        while (($data = fgetcsv($fileHandle, 99999, ",")) !== FALSE) {
            for($i=0;$i<sizeof($fieldNames);$i++){
                $formData[$totalRecords][$fieldNames[$i]] = isset($data[$i])?$data[$i]:NULL;
            }
            $totalRecords++;
        }
        
        foreach($formData as $rowNo=>$row){
            $d = [];
            $row = $this->manipulateImportDataRow($row);
            if($baseFieldSetName!=''){
                $d[$baseFieldSetName] = $row;
            }
            else{
               $d = $row;
            }
            
            $obj = $this->objectRepository->findOneBy([$keyFieldName=>$row[$keyFieldName]]);
            if(is_object($obj) && $updateIfFound){
                //record exists so update it
                $this->form->bind($obj);
                $this->setData($d);
                if($this->update()==false){
                    $msg = "Data validation error at row ".$rowNo." during update";
                    if($ignoreErrors){
                        $ret['messages'][] = $msg;
                        $ret['errors'][] = ['rowNo'=>$rowNo,'message'=>$this->form->getMessages()];
                    }
                    else{
                        throw new \Exception($msg);
                    }
                }else{
                    $ret['messages'][] = "Data updated for ".$keyFieldName.' : '.$row[$keyFieldName];
                    $ret['rowsUpdated']++;
                }
            }else{
                //record doesnot exist so insert it
                $this->setData($d);
                if($this->create()==false){
                    $msg = "Data validation error at row ".$rowNo;
                    if($ignoreErrors){
                        $ret['messages'][] = $msg;
                        $ret['errors'][] = ['rowNo'=>$rowNo,'message'=>$this->form->getMessages()];
                    }
                    else{
                        throw new \Exception($msg);
                    }
                }else{
                    $ret['messages'][] = "Data inserted for ".$keyFieldName.' : '.$row[$keyFieldName];
                    $ret['rowsInserted']++;
                }
            }
        }
        $ret['result'] = true;
        return $ret;
    }

    /**
     * Use this function in extended classes to manipulate data row before import.
     * It can be used to set application generated value which the user will not provide in csv file.
     * @param array $row
     * @return array
     */
    protected function manipulateImportDataRow($row){
        return $row;
    }
    
    /**
     * Returns FQCN of all the entities managed by $persistanceManager
     * @param EntityManagerInterface $persistanceManager
     * @return array
     */
    static public function getAllEntityNames(EntityManagerInterface $persistanceManager) {
        $metadata = $persistanceManager->getMetadataFactory()->getAllMetadata();
        $entityNames = [];
        foreach ($metadata as $classMeta) {
            $entityNames[] = $classMeta->getName(); // Entity FQCN
        }
        return $entityNames;
    }

}
