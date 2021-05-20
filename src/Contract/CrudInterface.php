<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud\Contract;

use BplCrud\QueryFilter;

interface CrudInterface extends MapperInterface {

    /**
     * Get form object
     * @return \Laminas\Form\FormInterface
     */
    public function getForm(): \Laminas\Form\FormInterface;
}
