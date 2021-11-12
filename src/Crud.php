<?php

namespace BplCrud;

use Laminas\Form\FormInterface;
use BplCrud\Form\FormRenderer;
use BplCrud\Contract\CrudInterface;
use BplCrud\Contract\MapperInterface;
use BplCrud\Contract\FormRendererInterface;

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */
class Crud implements CrudInterface {

    /**
     * Storage operations are done through mapper
     * @var \BplCrud\Contract\MapperInterface 
     */
    protected $mapper;

    /**
     * Form for record creation and update, contains data validators and filters
     * @var \Laminas\Form\FormInterface 
     */
    protected $form;

    public function __construct(MapperInterface $mapper, FormInterface $form) {
        $this->mapper = $mapper;
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    public function create($object) {
        return $this->mapper->create($object);
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
        return;
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
        return;
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
        return;
    }

    /**
     * This method converts array into nested HTML list
     * @param array $array
     * @return string
     */
    protected function getList($array, $listStyle = "ul") {
        $ret = '';
        if (is_array($array) && sizeof($array) > 0) {
            $ret = '<' . $listStyle . '>';
            foreach ($array as $key => $value) {
                $ret .= '<li>' . $key;
                if (is_array($value)) {
                    $ret .= $this->getList($value);
                } else {
                    $ret .= ' - ' . $value;
                }
                $ret .= '</li>';
            }
            $ret .= '</' . $listStyle . '>';
        }
        return $ret;
    }

    /**
     * Return total no of pages
     * @param \BplCrud\QueryFilter | array $queryFilter
     * @param int $recordPerPage
     * @return type
     */
    public function getNoOfPages($queryFilter, $recordPerPage) {
        return $this->mapper->getNoOfPages($queryFilter, $recordPerPage);
    }

    /**
     * Returns total no of records for a given filter condition
     * @param \BplCrud\QueryFilter | array $queryFilter
     * @return int
     */
    public function getTotalRecordCount($queryFilter) {
        return $this->mapper->getTotalRecordCount($queryFilter);
    }

    /**
     * Set form attribute
     * @param string $key
     * @param mixed $attribute
     * @return void
     */
    public function setFormAttribute($key, $attribute) {
        $this->form->setAttribute($key, $attribute);
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(): FormInterface {
        return $this->form;
    }

    public function findOneById($id) {
        return $this->mapper->findOneById($id);
    }

}
