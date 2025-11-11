<?php

namespace App\Entity;

use App\Repository\FactureParamRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FactureParamRepository::class)]
#[ORM\Table(name: 'facture_param')]
class FactureParam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Facture::class, inversedBy: 'valeurs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Facture $facture = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $paramGlobal = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $cle = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $valeur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): self
    {
        $this->facture = $facture;

        return $this;
    }

    public function getParamGlobal(): ?string
    {
        return $this->paramGlobal;
    }

    public function setParamGlobal(string $paramGlobal): self
    {
        $this->paramGlobal = $paramGlobal;

        return $this;
    }

    public function getCle(): ?string
    {
        return $this->cle;
    }

    public function setCle(string $cle): self
    {
        $this->cle = $cle;

        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(?string $valeur): self
    {
        $this->valeur = $valeur;

        return $this;
    }
}
