<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud;

use Laminas\Form\FormInterface;
use Doctrine\ORM\EntityManagerInterface;
use BplCrud\Form\FormRenderer;
use BplCrud\Contract\MapperInterface;
use BplCrud\Mapper\DoctrineMapper;
use BplCrud\Contract\FormRendererInterface;

class Crud implements MapperInterface {

    /**
     * Database operations are done through mapper
     * @var \BplCrud\Contract\MapperInterface 
     */
    protected $mapper;

    /**
     * Form through which data u
     * @var \Laminas\Form\FormInterface 
     */
    protected $form;

    public function __construct(EntityManagerInterface $em, FormInterface $form, $entityName) {
        $this->mapper = new DoctrineMapper($em, $entityName);
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    public function create($object) {
        return $this->mapper->save($object);
    }

    /**
     * {@inheritdoc}
     */
    public function read($queryFilter, $offset = 0, $limit = 10, $orderBy = []) {
        return $this->mapper->read($queryFilter, $offset, $limit, $orderBy);
    }

    /**
     * {@inheritdoc}
     */
    public function update($object) {
        return $this->mapper->update($object);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($object) {
        $this->mapper->delete($object);
        return;
    }

    /**
     * Bind object to form
     * @param object $object
     */
    public function bind($object) {
        $this->form->bind($object);
    }
    
    /**
     * Get object from form
     * @param object $object
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
     * Method setData() must be called before this
     * @return boolean
     */
    public function isFormValid() {
        return $this->form->isValid();
    }

    /**
     * Get validation messages
     * @return array
     */
    public function getErrorMessages() {
        return $this->form->getMessages();
    }

    /**
     * Get form errors in html list
     * @return type
     */
    public function getErrorsHtml() {
        $errors = $this->getMessages();
        return $this->getList($errors);
    }
    
    /**
     * Display form in html
     * @param FormRendererInterface|NULL $renderer
     */
    public function displayForm(FormRendererInterface $renderer = NULL) {
        if ($renderer == NULL) {
            $renderer = new FormRenderer();
        }
        $renderer->displayForm($this->form);
    }

    /**
     * This method converts array into nested HTML list
     * @param array $array
     * @return string
     */
    protected function getList($array) {
        $ret = '';
        if (is_array($array) && sizeof($array) > 0) {
            $ret = '<ul>';
            foreach ($array as $key => $value) {
                $ret .= '<li>' . $key;
                if (is_array($value)) {
                    $ret .= $this->getList($value);
                } else {
                    $ret .= ' - ' . $value;
                }
                $ret .= '</li>';
            }
            $ret .= '</ul>';
        }
        return $ret;
    }

}
