<?php

/**
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */

namespace BplCrud;

use Doctrine\ORM\QueryBuilder;
use BplCrud\Exception\InvalidComparatorException;
use BplCrud\Exception\InvalidExpressionCombinerException;

class QueryFilter {

    public static $eq = 'eq';
    public static $gt = 'gt';
    public static $lt = 'lt';
    public static $gte = 'gte';
    public static $lte = 'lte';
    public static $neq = 'neq';
    public static $isNull = 'isNull';
    public static $isNotNull = 'isNotNull';
    public static $like = 'like';
    public static $notLike = 'notLike';
    public static $and = 'and';
    public static $or = 'or';

    /**
     *
     * @var array 
     */
    public $rawFilterData = [];

    /**
     * @var array 
     */
    public $queryFilterArray = [];

    /**
     * Takes an array of 
     * <code>
     * $filter = [
     *     [
     *         "colName"=>"",
     *         "value"=>"",
     *         "compareType"=>"",
     *         "perviousFilterCombiner"=>"",
     *     ],
     *     .......
     * ]
     * </code>
     * @param array $queryFilterArray
     */
    public function __construct($queryFilterArray, $rawFilterData = []) {
        $this->queryFilterArray = $queryFilterArray;
        $this->rawFilterData = $rawFilterData;
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    public function getModifiedQueryBuilder(QueryBuilder $qb) {
        $paramCount = 1;
        $params = [];
        foreach ($this->queryFilterArray as $filter) {
            $colName = $filter['colName'];
            $value = $filter['value'];
            $compareType = $filter['compareType'];
            $perviousFilterCombiner = $filter['perviousFilterCombiner'];

            if (strpos($colName, '.') === false) {
                $colName = 'u.' . $colName;
            }

            $cleanColName = str_replace('.', '_', $colName);

            if ($compareType == self::$eq) {
                $expr = $qb->expr()->eq($colName, ':' . $cleanColName . $paramCount);
            } elseif ($compareType == self::$gt) {
                $expr = $qb->expr()->gt($colName, ':' . $cleanColName . $paramCount);
            } elseif ($compareType == self::$gte) {
                $expr = $qb->expr()->gte($colName, ':' . $cleanColName . $paramCount);
            } elseif ($compareType == self::$isNotNull) {
                $expr = $qb->expr()->isNotNull($colName);
            } elseif ($compareType == self::$isNull) {
                $expr = $qb->expr()->isNull($colName);
            } elseif ($compareType == self::$like) {
                $expr = $qb->expr()->like($colName, ':' . $cleanColName . $paramCount);
            } elseif ($compareType == self::$lt) {
                $expr = $qb->expr()->lt($colName, ':' . $cleanColName . $paramCount);
            } elseif ($compareType == self::$lte) {
                $expr = $qb->expr()->lte($colName, ':' . $cleanColName . $paramCount);
            } elseif ($compareType == self::$neq) {
                $expr = $qb->expr()->neq($colName, ':' . $cleanColName . $paramCount);
            } elseif ($compareType == self::$notLike) {
                $expr = $qb->expr()->notLike($colName, ':' . $cleanColName . $paramCount);
            } else {
                throw new InvalidComparatorException("Invalid comparator selected");
            }
            $params[$cleanColName . $paramCount] = $value;
            if ($perviousFilterCombiner == self::$and) {
                $qb->andWhere($expr);
            } elseif ($perviousFilterCombiner == self::$or) {
                $qb->orWhere($expr);
            } else {
                throw new InvalidExpressionCombinerException("Invalid expression combiner provided only 'and' and 'or' allowed.");
            }
            $paramCount++;
        }
        $qb->setParameters($params);
        return $qb;
    }

}
