<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud\Contract;
use BplCrud\QueryFilter;

interface CrudInterface{
    public function create();
    public function read(QueryFilter $queryFilter, $offset = 0, $limit = 10);
    public function update();
    public function delete($object);
}