<?php

namespace App\Repository;

use App\Entity\PurchaseOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PurchaseOrder>
 *
 * @method PurchaseOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurchaseOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method PurchaseOrder[]    findAll()
 * @method PurchaseOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurchaseOrderRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, PurchaseOrder::class);
  }

  public function find_po_lines(?int $po_num)
  {
    return $this->createQueryBuilder('p')
      ->join('p.po_lines', 'po_line')
      ->andWhere('p.po_num = :po_num')
      ->setParameter('po_num', $po_num)
      ->addSelect('po_line')
      ->getQuery()
    ;
  }

  /**
   * @author Daniel Boling
   * @return Item[] Returns an array of Item objects
   */
  public function filter(array $params)
  {
    $qb = $this->createQueryBuilder('p');
    if (isset($params['item_name']))
    {
      $qb
        ->setParameter('item_name', '%'.$params['item_name'].'%')
        ->andWhere('item.name like :item_name')
      ;
    }
    if (isset($params['location']) && $params['location'] !== '')
    {
      $qb
        ->setParameter('loc_id', $params['location'])
        ->andWhere('location.id in (:loc_id)')
      ;
    }
    return $qb->getQuery();
  }


//    /**
//     * @return PurchaseOrder[] Returns an array of PurchaseOrder objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PurchaseOrder
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
