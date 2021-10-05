<?php

namespace App\Repository;

use App\Entity\Geogroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Geogroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method Geogroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method Geogroup[]    findAll()
 * @method Geogroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeogroupsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Geogroup::class);
    }


    /**
     * @return Geogroup[]
     */
    public function findAllPublic($x = 0): array
    {
        $qb = $this->createQueryBuilder('gg')
            ->where('gg.password IS NULL')
            ->orderBy('gg.name', 'ASC')
            ->getQuery();

        $r = $qb->execute();
        return $r;
    }

//    /**
//     * @return Geogroup[] Returns an array of Geogroup objects
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
    public function findOneBySomeField($value): ?Geogroup
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
