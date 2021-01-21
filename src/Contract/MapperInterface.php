<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud\Contract;
use BplCrud\QueryFilter;

interface MapperInterface {

    /**
     * Save $object in database
     * @param type $object
     */
    public function create($object);

    /**
     * Update record 
     * @param type $object
     */
    public function update($object);

    /**
     * Delete $object from database
     * @param type $object
     */
    public function delete($object);

    /**
     * Read records from database
     * 
     * @param \BplCrud\QueryFilter | array $queryFilter
     * @param int $offset default 0
     * @param int $limit default 10
     * @param array type $orderBy Order by is of type ["columnName1"=>"ASC/DESC", "columnName2"=>"ASC/DESC"]
     */
    public function read($queryFilter, $offset = 0, $limit = 10, $orderBy = []);
}