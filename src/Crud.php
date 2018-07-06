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
    protected $em;

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
     * @param EntityManagerInterface $em
     * @param string $objectClass
     */
    public function __construct(FormInterface $form, EntityManagerInterface $em, $objectClass = '', FormRendererInterface $formRenderer = NULL) {
        $this->form = $form;
        $this->em = $em;
        if ($objectClass == '') {
            $objectClass = get_class($this->form->getObject());
        }
        $this->objectClass = $objectClass;
        $this->objectRepository = $this->em->getRepository($objectClass);

        /**
         * If renderer is not provided; use default renderer
         */
        if ($formRenderer == NULL) {
            $this->formRenderer = new FormRenderer();
        } else {
            $this->formRenderer = $formRenderer;
        }
    }

    public function setForm(FormInterface $form){
        $this->form = $form;
    }
    /**
     * @return string
     */
    public function getObjectClass() {
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
        $valid = $this->isvalid();
        if ($valid) {
            $this->em->persist($this->form->getObject());
            $this->em->flush();
        }
        return $valid;
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
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')->from($this->objectClass, 'u');
        $qb = $queryFilter->getModifiedQueryBuilder($qb);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        foreach ($orderBy as $sort => $order) {

            if (strpos($sort, '.') === false) {
                $sort = 'u.' . $sort;
            }

            $qb->addOrderBy($sort, $order);
        }

        $query = $qb->getQuery();

        $paginator = new Paginator($query);
        return $paginator;
    }
    
    /**
     * Extracts object from the form and updates database entry corresponding to that object
     * @return boolean
     */
    public function update() {
        $valid = $this->isvalid();
        if ($valid) {
            $this->em->merge($this->form->getObject());
            $this->em->flush();
        }
        return $valid;
    }

    /**
     * Return total count of records in a table marching $queryFilter
     * @param \BplCrud\QueryFilter $queryFilter
     * @return integer
     */
    public function getTotalRecordCount(QueryFilter $queryFilter) {
        $qb = $this->em->createQueryBuilder();
        $qb->select('count(u)')->from($this->objectClass, 'u');
        $qb = $queryFilter->getModifiedQueryBuilder($qb);
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 
     * @param object $entity
     */
    public function saveEntity($entity) {
        $key = $this->em->getClassMetadata(get_class($entity))->getSingleIdentifierFieldName();
        $entityMethod = 'get' . ucfirst($key);
        $persistanceMethod = $entity->$entityMethod() == NULL ? 'persist' : 'merge';
        $this->em->$persistanceMethod($entity);
        $this->em->flush();
    }

    /**
     * Deletes given object from database
     * @param object $object
     * @return boolean
     */
    public function delete($object) {
        $this->em->remove($object);
        $this->em->flush();
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

    /**
     * Import data from csv uses header row as field names
     * @param string $absFilePath
     * @param string $keyFieldName
     * @param string $fieldSetName
     * @return array
     * @throws \Exception
     */
    public function importFromCSV($absFilePath, $keyFieldName, $baseFieldSetName = '', $updateIfFound = true, $ignoreErrors = true) {
        $ret = ['result' => false, 'messages' => [], 'errors' => [], 'rowsInserted' => 0, 'rowsUpdated' => 0];
        $totalRecords = 0;
        $formData = [];

        $validator = new \Zend\Validator\File\Extension(["extention" => 'csv', 'case' => false]);
        if (!$validator->isValid($absFilePath)) {
            throw new \Exception("File must exist and be of .csv type.");
        }
        $fileHandle = fopen($absFilePath, 'r');
        $fieldNames = fgetcsv($fileHandle, 99999, ",");
        
        if ($keyFieldName!=='' && !in_array($keyFieldName, $fieldNames)) {
            throw new \Exception($keyFieldName . " must be present and cannot be empty");
        }

        /**
         * Create associative array of data
         */
        while (($data = fgetcsv($fileHandle, 99999, ",")) !== FALSE) {
            for ($i = 0; $i < sizeof($fieldNames); $i++) {
                $formData[$totalRecords][$fieldNames[$i]] = isset($data[$i]) ? $data[$i] : NULL;
            }
            $totalRecords++;
        }

        foreach ($formData as $rowNo => $row) {
            $d = [];
            $actualRowNo = $rowNo+2;
            $row = $this->manipulateImportDataRow($row);
            if ($baseFieldSetName != '') {
                $d[$baseFieldSetName] = $row;
            } else {
                $d = $row;
            }

            $obj = $keyFieldName!==''?$this->objectRepository->findOneBy([$keyFieldName => $row[$keyFieldName]]):null;
            
            if (is_object($obj)) {
                if($updateIfFound){
                    //record exists so update it
                    $this->form->bind($obj);
                    $this->setData($d);
                    if ($this->update() == false) {
                        $msg = "Data validation error at row " . $actualRowNo . " during update";
                        if ($ignoreErrors) {
                            $ret['messages'][] = $msg;
                            $ret['errors'][] = ['rowNo' => $actualRowNo, 'message' => $this->form->getMessages()];
                        } else {
                            throw new \Exception($msg);
                        }
                    } else {
                        $ret['messages'][] = "Data updated for " . $keyFieldName . ' : ' . $row[$keyFieldName];
                        $ret['rowsUpdated'] ++;
                    }
                }else{
                    continue;
                }
            } else {
                //record doesnot exist so insert it
                $this->form->bind(new $this->objectClass);
                $this->setData($d);
                if ($this->create() == false) {
                    $msg = "Data validation error at row " . $actualRowNo;
                    if ($ignoreErrors) {
                        $ret['messages'][] = $msg;
                        $ret['errors'][] = ['rowNo' => $actualRowNo, 'message' => $this->form->getMessages()];
                    } else {
                        throw new \Exception($msg);
                    }
                } else {
                    $ret['messages'][] = "Data inserted for row number = " . $actualRowNo;
                    $ret['rowsInserted'] ++;
                }
            }
            $this->em->clear();
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
    protected function manipulateImportDataRow($row) {
        return $row;
    }

    /**
     * Returns FQCN of all the entities managed by $em
     * @param EntityManagerInterface $em
     * @return array
     */
    static public function getAllEntityNames(EntityManagerInterface $em) {
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $entityNames = [];
        foreach ($metadata as $classMeta) {
            $entityNames[] = $classMeta->getName(); // Entity FQCN
        }
        return $entityNames;
    }

}
