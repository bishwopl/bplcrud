<?php

namespace BplCrud\Contract;

use BplCrud\QueryFilter;

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */
interface MapperInterface {

    /**
     * Retrieve an object form ID
     * @param mixed $id
     */
    public function findOneById($id);

    /**
     * Save $object in storage
     * @param type $object
     */
    public function create($object);

    /**
     * Update record 
     * @param type $object
     */
    public function update($object);

    /**
     * Delete $object from storage
     * @param type $object
     */
    public function delete($object);

    /**
     * Read records from storage
     * 
     * @param \BplCrud\QueryFilter | array $queryFilter
     * @param int $offset default 0
     * @param int $limit default 10
     * @param array type $orderBy Order by is of type ["columnName1"=>"ASC/DESC", "columnName2"=>"ASC/DESC"]
     */
    public function read($queryFilter, $offset = 0, $limit = 10, $orderBy = []);

    /**
     * Return total no of pages
     * @param \BplCrud\QueryFilter | array $queryFilter
     * @param int $recordPerPage
     * @return type
     */
    public function getNoOfPages($queryFilter, $recordPerPage);

    /**
     * Returns total no of records for a given filter condition
     * @param \BplCrud\QueryFilter | array $queryFilter
     * @return int
     */
    public function getTotalRecordCount($queryFilter);
}
