<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "catalogues")]
class Catalogues
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: "La référence ne doit pas être vide.")]
    #[Assert\Length(
        max: 20,
        maxMessage: "La référence ne peut pas faire plus de {{ limit }} caractères."
    )]
    #[ORM\Column(length: 255)]
    private ?string $refecata = null;

    #[Assert\Length(
        max: 50,
        maxMessage: "Le code barre ne peut pas faire plus de {{ limit }} caractères."
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $codebarr = null;

    #[Assert\NotBlank(message: "Le type est incorrect.")]
    #[ORM\Column]
    private ?int $typecata = null;

    #[Assert\NotBlank(message: "La designation ne doit pas être vide.")]
    #[Assert\Length(
        max: 250,
        maxMessage: "La designation ne peut pas faire plus de {{ limit }} caractères."
    )]
    #[ORM\Column(length: 250)]
    private ?string $designation = null;

    #[ORM\Column]
    private ?bool $etatAchat = null;

    #[ORM\Column]
    private ?bool $etatVente = null;

    #[ORM\Column(nullable: true)]
    private ?float $prixAchat = null;

    #[ORM\Column(nullable: true)]
    private ?float $prixVente = null;

    #[ORM\Column(nullable: true)]
    private ?float $prixVenteMin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $marge = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $uniteMesure = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCrea = null;

    #[ORM\Column]
    private ?int $etatCata = null;

    // =======================
    // Getters & Setters
    // =======================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRefecata(): ?string
    {
        return $this->refecata;
    }

    public function setRefecata(string $refecata): static
    {
        $this->refecata = $refecata;
        return $this;
    }

    public function getCodebarr(): ?string
    {
        return $this->codebarr;
    }

    public function setCodebarr(?string $codebarr): static
    {
        $this->codebarr = $codebarr;
        return $this;
    }

    public function getTypecata(): ?int
    {
        return $this->typecata;
    }

    public function setTypecata(int $typecata): static
    {
        $this->typecata = $typecata;
        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): static
    {
        $this->designation = $designation;
        return $this;
    }

    public function isEtatAchat(): ?bool
    {
        return $this->etatAchat;
    }

    public function setEtatAchat(bool $etatAchat): static
    {
        $this->etatAchat = $etatAchat;
        return $this;
    }

    public function isEtatVente(): ?bool
    {
        return $this->etatVente;
    }

    public function setEtatVente(bool $etatVente): static
    {
        $this->etatVente = $etatVente;
        return $this;
    }

    public function getPrixAchat(): ?float
    {
        return $this->prixAchat;
    }

    public function setPrixAchat(?float $prixAchat): static
    {
        $this->prixAchat = $prixAchat;
        return $this;
    }

    public function getPrixVente(): ?float
    {
        return $this->prixVente;
    }

    public function setPrixVente(?float $prixVente): static
    {
        $this->prixVente = $prixVente;
        return $this;
    }

    public function getPrixVenteMin(): ?float
    {
        return $this->prixVenteMin;
    }

    public function setPrixVenteMin(?float $prixVenteMin): static
    {
        $this->prixVenteMin = $prixVenteMin;
        return $this;
    }

    public function getMarge(): ?string
    {
        return $this->marge;
    }

    public function setMarge(?string $marge): static
    {
        $this->marge = $marge;
        return $this;
    }

    public function getUniteMesure(): ?string
    {
        return $this->uniteMesure;
    }

    public function setUniteMesure(?string $uniteMesure): static
    {
        $this->uniteMesure = $uniteMesure;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateCrea(): ?\DateTimeInterface
    {
        return $this->dateCrea;
    }

    public function setDateCrea(\DateTimeInterface $dateCrea): static
    {
        $this->dateCrea = $dateCrea;
        return $this;
    }

    public function getEtat(): ?int
    {
        return $this->etatCata;
    }

    public function setEtat(int $etatCata): static
    {
        $this->etatCata = $etatCata;
        return $this;
    }
}

