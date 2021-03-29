<?php

namespace BplCrud\Contract;

use BplCrud\QueryFilter;

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */
interface MapperInterface extends CrudInterface {

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
