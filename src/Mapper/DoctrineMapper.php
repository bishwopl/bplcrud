<?php

namespace BplCrud\Mapper;

/**
 * Description of AbstractMapper
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use BplCrud\Contract\MapperInterface;
use BplCrud\QueryFilter;

/**
 * Doctrine Mapper
 *
 * Provides common doctrine methods
 */
class DoctrineMapper implements MapperInterface {

    protected $entityManager;
    protected $entityName;

    public function __construct(EntityManager $em, $entityName) {
        $this->entityManager = $em;
        $this->entityName = $entityName;
    }

    public function getEntityManager(): EntityManager {
        return $this->entityManager;
    }

    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function getDatabase(): Connection {
        return $this->entityManager->getConnection();
    }

    public function getRepository(): EntityRepository {
        return $this->entityManager->getRepository($this->entityName);
    }
    
    public function getPrototype() {
        return new $this->entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function create($e) {
        $this->getEntityManager()->persist($e);
        $this->getEntityManager()->flush($e);
    }

    /**
     * {@inheritdoc}
     */
    public function update($e) {
        $this->getEntityManager()->merge($e);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($e) {
        $this->getEntityManager()->remove($e);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function read($queryFilter, $offset = 0, $limit = 10, $orderBy = []) {
        if(is_array($queryFilter)){
            $queryFilter = QueryFilter::create($queryFilter, 'and');
        }
        
        if(!$queryFilter instanceof QueryFilter){
            throw new \Exception("Query filter must be provided to read data");
        }
        
        $qb = $queryFilter->getModifiedQueryBuilder(
                $this->getRepository()->createQueryBuilder("u")
        );
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        foreach ($orderBy as $sort => $order) {

            if (strpos($sort, '.') === false) {
                $sort = 'u.' . $sort;
            }

            $qb->addOrderBy($sort, $order);
        }

        $query = $qb->getQuery();

        $paginator = new Paginator($query);
        return $paginator;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getNoOfPages($queryFilter, $recordPerPage){
        $recordCount = $this->getTotalRecordCount($queryFilter);
        return ceil($recordCount/$recordPerPage);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTotalRecordCount($queryFilter){
        if(is_array($queryFilter)){
            $queryFilter = QueryFilter::create($queryFilter, 'and');
        }
        $qb = $queryFilter->getModifiedQueryBuilder(
                $this->getRepository()->createQueryBuilder("u")
        );
        return $qb->getQuery()->getSingleScalarResult();
    }

}
