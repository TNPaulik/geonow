<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Location|null find($id, $lockMode = null, $lockVersion = null)
 * @method Location|null findOneBy(array $criteria, array $orderBy = null)
 * @method Location[]    findAll()
 * @method Location[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Location::class);
    }



    /**
     * @param $ltd
     * @param $lgt
     * @param $diff in meters
     * @return Location[]
     */
    public function findAllLocationsNear($ltd, $lgt, $diff): array
    {
        $diff = $diff * 0.0001;
        $qb = $this->createQueryBuilder('l')
            ->andWhere('l.ltd > :ltd - :diff')
            ->andWhere('l.lgt > :lgt - :diff')
            ->andWhere('l.ltd < :ltd + :diff')
            ->andWhere('l.lgt < :lgt + :diff')
            ->andWhere('l.status = :active')
            ->setParameter('ltd', $ltd)
            ->setParameter('lgt', $lgt)
            ->setParameter('diff', $diff)
            ->setParameter('active', Location::$ACTIVE)
            ->orderBy('l.id', 'ASC')
            ->getQuery();

        $r = $qb->execute();
        return $r;

        // to get just one result:
        // $product = $qb->setMaxResults(1)->getOneOrNullResult();
    }
}
