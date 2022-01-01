<?php

namespace BplCrud\Mapper;

/**
 * Description of Mapper
 * @author Bishwo Prasad Lamichhane <bishwo.prasad@gmail.com>
 */
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
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
    protected $className;

    public function __construct(ObjectManager $em, $className) {
        $this->entityManager = $em;
        $this->className = $className;
    }

    public function getObjectManager(): ObjectManager {
        return $this->entityManager;
    }

    public function getRepository(): ObjectRepository {
        return $this->entityManager->getRepository($this->className);
    }
    
    public function getPrototype() {
        return new $this->className;
    }

    /**
     * {@inheritdoc}
     */
    public function save($e) {
        $this->getObjectManager()->persist($e);
        $this->getObjectManager()->flush($e);
    }

    /**
     * {@inheritdoc}
     */
    public function update($e) {
        $this->getObjectManager()->merge($e);
        $this->getObjectManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($e) {
        $this->getObjectManager()->remove($e);
        $this->getObjectManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function findBy($criteria, $offset = 0, $limit = 10, $orderBy = []) {
        if(is_array($criteria)){
            $criteria = QueryFilter::create($criteria, 'and');
        }
        
        if(!$criteria instanceof QueryFilter){
            throw new \Exception("Query filter must be provided to read data");
        }
        
        $qb = $criteria->getModifiedQueryBuilder(
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
    public function getNoOfPages($criteria, $recordPerPage){
        $recordCount = $this->getTotalRecordCount($criteria);
        return ceil($recordCount/$recordPerPage);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTotalRecordCount($criteria){
        $ids = $this->entityManager->getClassMetadata($this->className)->getIdentifier();
        $key = $ids;
        if(is_array($ids)){
            $key = $ids[0];
        }
        if(is_array($criteria)){
            $criteria = QueryFilter::create($criteria, 'and');
        }
        $qb = $criteria->getModifiedQueryBuilder(
                $this->getRepository()->createQueryBuilder('u')
        );
        $qb->select("COUNT(u.$key)");
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function find($id) {
        return $this->getRepository()->find($id);
    }

    public function findAll() {
        return $this->getRepository()->findAll();
    }

    public function findOneBy($criteria) {
        return $this->getRepository()->findOneBy($criteria);
    }

    public function getClassName(): string {
        return $this->className;
    }

}
