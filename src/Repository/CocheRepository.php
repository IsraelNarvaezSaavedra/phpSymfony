<?php

namespace App\Repository;

use App\Entity\Coche;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coche>
 */
class CocheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coche::class);
    }

    //    /**
    //     * @return Coche[] Returns an array of Coche objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Coche
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByModelo($modelo)
    {
        return $this->createQueryBuilder('c')
            ->where('c.modelo LIKE :buscar')
            ->setParameter('buscar', '%'. $modelo. '%')
        ;
    }

    public function findByMarca($marca)
    {
        return $this->createQueryBuilder('c')
            ->where('c.marca LIKE :buscar')
            ->setParameter('buscar', '%'. $marca. '%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findCoche($marca, $modelo)
    {
        return $this->createQueryBuilder('c')
            ->where('c.modelo LIKE :modelo AND c.marca LIKE :marca')
            ->setParameter('modelo', '%'. $modelo. '%')
            ->setParameter( 'marca', '%' . $marca . '%')
            ->getQuery()
            ->getResult()
        ;
    }
}
