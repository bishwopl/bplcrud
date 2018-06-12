<?php

namespace BplCrud\Contract;
use Zend\Form\FormInterface;

interface FormRendererInterface {

    public function displayForm(FormInterface $form);
}
