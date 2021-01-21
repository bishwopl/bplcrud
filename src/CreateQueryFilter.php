<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud;

use BplCrud\QueryFilter;

/**
 * Creates a query filter compatible with \BplCrud\Crud from 1-D array like 
 * ["key1"=>"value1","key2"=>"value2"]
 * where key1, key2 are column names and value1 and value2 search terms.
 * If value is number then "equal to" comparator is used otherwise "like"
 * comparator is used.
 */
class CreateQueryFilter {

    /**
     * Creates a query filter compatible with \BplCrud\Crud from 1-D array like 
     * ["key1"=>"value1","key2"=>"value2"]
     * where key1, key2 are column names and value1 and value2 search terms.
     * If value is number then "equal to" comparator is used otherwise "like"
     * comparator is used.
     * 
     * @param array $filter
     * @param string $className FQCN of entity
     * @return \BplCrud\QueryFilter
     */
    public static function create($filter, $combineOperator = "and", $className = '') {
        $criteria = [];
        foreach ($filter as $key => $value) {
            if ($className != '' && !property_exists($className, $key)) {
                continue;
            }
            if ($value == '' || is_array($value)) {
                continue;
            }
            $compare = QueryFilter::$eq;

            if (strtolower($value) == "null") {
                $compare = QueryFilter::$isNull;
            } elseif (!is_numeric($value)) {
                $compare = QueryFilter::$like;
                $value = "%" . $value . "%";
            }
            $criteria[] = ["colName" => $key, "value" => $value, "compareType" => $compare, "perviousFilterCombiner" => $combineOperator,];
        }
        $queryFilter = new QueryFilter($criteria, $filter);
        return $queryFilter;
    }

}
