<?php

namespace App\Repository;

use App\Entity\Geooption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Geooption|null find($id, $lockMode = null, $lockVersion = null)
 * @method Geooption|null findOneBy(array $criteria, array $orderBy = null)
 * @method Geooption[]    findAll()
 * @method Geooption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeooptionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Geooption::class);
    }

//    /**
//     * @return Geooption[] Returns an array of Geooption objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Geooption
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
