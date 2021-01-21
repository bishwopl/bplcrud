<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud\Contract;

use Laminas\Form\FormInterface;

interface FormRendererInterface {

    public function displayForm(FormInterface $form);
}
