<?php

namespace App\Repository;

use App\Entity\Facture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    // === Méthodes personnalisées possibles ===
    // Exemple : récupérer toutes les factures d'un client
    /*
    public function findByTier($tierId)
    {
        return $this->createQueryBuilder('f')
                    ->andWhere('f.tier = :tier')
                    ->setParameter('tier', $tierId)
                    ->getQuery()
                    ->getResult();
    }
    */
}

