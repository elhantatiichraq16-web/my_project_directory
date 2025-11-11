<?php

namespace App\Repository;

use App\Entity\FactureParam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FactureParamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactureParam::class);
    }

    /**
     * Active / désactive un paramètre de facture (toggle on/off)
     */
    public function toggleParam(int $factureId, int $cleId): void
    {
        $em = $this->getEntityManager();

        $param = $this->findOneBy([
            'factureId' => $factureId,
            'cleId' => $cleId
        ]);

        if ($param) {
            $param->setValeur($param->getValeur() === 'on' ? 'off' : 'on');
            $em->persist($param);
            $em->flush();
        }
    }

    /**
     * Récupère tous les paramètres de factures avec leur titre (pour affichage)
     */
    public function findAllWithTitles(): array
    {
        return $this->createQueryBuilder('fp')
            ->select('fp')
            ->orderBy('fp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les paramètres liés à une facture donnée
     */
    public function findByFactureId(int $factureId): array
    {
        return $this->createQueryBuilder('fp')
            ->where('fp.factureId = :factureId')
            ->setParameter('factureId', $factureId)
            ->orderBy('fp.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

