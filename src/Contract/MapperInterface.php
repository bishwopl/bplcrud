<?php

namespace BplCrud\Contract;

use Doctrine\Persistence\ObjectRepository;

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */
interface MapperInterface extends ObjectRepository {

    /**
     * Save $object in storage
     * @param type $object
     */
    public function save($object);

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
     * Return total no of pages for a given filter condition
     * @param \BplCrud\QueryFilter | array $criteria
     * @param int $recordPerPage
     * @return type
     */
    public function getNoOfPages($criteria, $recordPerPage);

    /**
     * Returns total no of records for a given filter condition
     * @param \BplCrud\QueryFilter | array $criteria
     * @return int
     */
    public function getTotalRecordCount($criteria);
}
