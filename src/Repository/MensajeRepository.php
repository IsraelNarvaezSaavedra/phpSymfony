<?php

namespace App\Repository;

use App\Entity\Mensaje;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mensaje>
 */
class MensajeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mensaje::class);
    }

    //    /**
    //     * @return Mensaje[] Returns an array of Mensaje objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Mensaje
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getUltimosMensajes($telefono): string
    {
        $cincoMin = new \DateTimeImmutable(-'5 minutes');
        $historial = $this->createQueryBuilder('m')
            ->where('m.telefono = :val')
            ->andWhere('m.fecha >= :limite')
            ->setParameter('limite', $cincoMin)
            ->setParameter('val', $telefono)
            ->orderBy('m.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
        ;

        //invierte el orden del array para que el mensaje más reciente quede al final, y así la ia entienda mejor el contexto
        $historial = array_reverse($historial);

        //convierte el array a texto para que luego la ia entienda el contexto
        return implode("\n", $historial);
    }

    public function getEstado($tel):string
    {
        $ultimoMensaje = $this->createQueryBuilder('m')
        ->andWhere('m.telefono = :tel')
        ->setParameter('tel', $tel)
        ->orderBy('m.id', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult()
        ;

        return $ultimoMensaje ? $ultimoMensaje->getEstado() : 'inicio';
    }

}
