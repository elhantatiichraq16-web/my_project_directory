<?php

namespace App\Repository;

use App\Entity\LigneFacture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LigneFacture>
 *
 * @method LigneFacture|null find($id, $lockMode = null, $lockVersion = null)
 * @method LigneFacture|null findOneBy(array $criteria, array $orderBy = null)
 * @method LigneFacture[]    findAll()
 * @method LigneFacture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LigneFactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneFacture::class);
    }

    public function add(LigneFacture $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LigneFacture $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // ✅ Récupérer toutes les lignes triées par ordre croissant
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ✅ Récupérer les lignes d’une facture spécifique triées par ordre
    public function findByFactureOrdered(int $factureId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.facture = :factureId')
            ->setParameter('factureId', $factureId)
            ->orderBy('l.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ✅ Exemple : Récupérer les lignes avec une unité donnée (ex: kg)
    public function findByUnite(string $unite): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.unite = :unite')
            ->setParameter('unite', $unite)
            ->getQuery()
            ->getResult();
    }
}
