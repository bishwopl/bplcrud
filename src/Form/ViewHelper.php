<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud\Form;

trait ViewHelper {

    /**
     *
     * @var \Zend\Form\View\Helper\FormRow 
     */
    public $rowHelper;

    /**
     *
     * @var \Zend\Form\View\Helper\FormElement 
     */
    public $elementHelper;

    /**
     *
     * @var \Zend\Form\View\Helper\FormCollection 
     */
    public $collectionHelper;

    /**
     *
     * @var \Zend\Form\View\Helper\Form 
     */
    public $formHelper;

    /**
     *
     * @var \Zend\Form\View\Helper\FormElementErrors 
     */
    public $errorHelper;

    /**
     *
     * @var \Zend\View\Renderer\PhpRenderer 
     */
    protected $renderer;

    public function initializeViewHelpers() {
        $this->rowHelper = new \Zend\Form\View\Helper\FormRow();
        $this->elementHelper = new \Zend\Form\View\Helper\FormElement();
        $this->collectionHelper = new \Zend\Form\View\Helper\FormCollection();
        $this->formHelper = new \Zend\Form\View\Helper\Form();
        $this->errorHelper = new \Zend\Form\View\Helper\FormElementErrors();

        $this->renderer = new \Zend\View\Renderer\PhpRenderer();
        $configProvider = new \Zend\Form\ConfigProvider();

        $this->renderer->setHelperPluginManager(new \Zend\View\HelperPluginManager(new \Zend\ServiceManager\ServiceManager(), $configProvider()['view_helpers']));
        $this->rowHelper->setView($this->renderer);
        $this->elementHelper->setView($this->renderer);
        $this->collectionHelper->setView($this->renderer);
    }

    /**
     * Displays the form without formatting 
     * This method can be used for testing
     */
    public function displayForm() {
        $this->form->prepare();
        echo $this->formHelper->openTag($this->form);

        foreach ($this->form as $element) {
            if ($element instanceof \Zend\Form\Fieldset) {
                foreach ($element as $em) {
                    echo '<div class="form_element">' . PHP_EOL;
                    if ($em instanceof \Zend\Form\Element\Collection) {
                        if($em->getCount()>0){
                            echo $this->collectionHelper->render($em) . PHP_EOL;
                        }
                    } else {
                        echo $this->rowHelper->render($em) . PHP_EOL;
                    }
                    echo '</div>' . PHP_EOL;
                }
            } else {
                echo '<div class="form_element">' . PHP_EOL;
                echo $this->rowHelper->render($element) . PHP_EOL;
                echo '</div>' . PHP_EOL;
            }
        }
        echo $this->formHelper->closeTag();
    }

}
