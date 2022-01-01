<?php

namespace BplCrud\Form;

use BplCrud\Contract\FormRendererInterface;
use Laminas\Form\FormInterface;

/**
 * Form display helper
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */
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

        $this->form->setAttributes([
            'class' => 'form form-horizontal'
        ]);

        echo $this->formHelper->openTag($this->form);

        foreach ($this->form as $element) {
            if ($element instanceof \Laminas\Form\Fieldset) {
                $this->displayFieldset($element, $this);
            } else {
                echo '<div class="form_element">' . PHP_EOL;
                echo $this->rowHelper->render($element) . PHP_EOL;
                echo '</div>' . PHP_EOL;
            }
        }
        echo $this->formHelper->closeTag();
    }

    protected function displayFieldset(\Laminas\Form\Fieldset $f, $renderer) {
        $isEmptyFieldset = sizeof($f->getElements());
        if ($isEmptyFieldset) {
            echo '<p></p><fieldset class="form-group border p-3">'
            . '<legend>'
            . ucwords($f->getName())
            . '</legend>';
        }
        foreach ($f as $em) {
            if ($em instanceof \Laminas\Form\Fieldset) {
                $this->displayFieldset($em, $renderer);
            } else {
                echo '<div class="form_element">' . PHP_EOL;
                if ($em instanceof \Laminas\Form\Element\Collection) {
                    if ($em->getCount() > 0) {
                        echo $renderer->collectionHelper->render($em) . PHP_EOL;
                    }
                } else {
                    echo $renderer->rowHelper->render($em) . PHP_EOL;
                }
                echo '</div>' . PHP_EOL;
            }
        }
        echo '</fieldset><p></p>';
    }

}
