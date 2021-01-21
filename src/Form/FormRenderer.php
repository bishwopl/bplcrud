<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud\Form;

use BplCrud\Contract\FormRendererInterface;
use Laminas\Form\FormInterface;

class FormRenderer implements FormRendererInterface {

    /**
     *
     * @var \Laminas\Form\View\Helper\FormRow 
     */
    public $rowHelper;

    /**
     *
     * @var \Laminas\Form\View\Helper\FormElement 
     */
    public $elementHelper;

    /**
     *
     * @var \Laminas\Form\View\Helper\FormCollection 
     */
    public $collectionHelper;

    /**
     *
     * @var \Laminas\Form\View\Helper\Form 
     */
    public $formHelper;

    /**
     *
     * @var \Laminas\Form\View\Helper\FormElementErrors 
     */
    public $errorHelper;

    /**
     *
     * @var \Laminas\View\Renderer\PhpRenderer 
     */
    protected $renderer;

    /**
     *
     * @var \Laminas\Form\FormInterface
     */
    protected $form;

    public function __construct() {
        $this->rowHelper = new \Laminas\Form\View\Helper\FormRow();
        $this->elementHelper = new \Laminas\Form\View\Helper\FormElement();
        $this->collectionHelper = new \Laminas\Form\View\Helper\FormCollection();
        $this->formHelper = new \Laminas\Form\View\Helper\Form();
        $this->errorHelper = new \Laminas\Form\View\Helper\FormElementErrors();

        $this->renderer = new \Laminas\View\Renderer\PhpRenderer();
        $configProvider = new \Laminas\Form\ConfigProvider();

        $this->renderer->setHelperPluginManager(new \Laminas\View\HelperPluginManager(new \Laminas\ServiceManager\ServiceManager(), $configProvider()['view_helpers']));
        $this->rowHelper->setView($this->renderer);
        $this->elementHelper->setView($this->renderer);
        $this->collectionHelper->setView($this->renderer);
    }

    public function displayForm(FormInterface $form) {
        $this->form = $form;
        $this->form->prepare();
        echo $this->formHelper->openTag($this->form);

        foreach ($this->form as $element) {
            if ($element instanceof \Laminas\Form\Fieldset) {
                foreach ($element as $em) {
                    echo '<div class="form_element">' . PHP_EOL;
                    if ($em instanceof \Laminas\Form\Element\Collection) {
                        if ($em->getCount() > 0) {
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
