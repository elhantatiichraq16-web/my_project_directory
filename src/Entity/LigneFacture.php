<?php

namespace App\Entity;

use App\Repository\LigneFactureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LigneFactureRepository::class)]
#[ORM\Table(name: 'ligne_facture')]
class LigneFacture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le nom du produit est obligatoire.")]
    private ?string $produit = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: "La quantité est obligatoire.")]
    #[Assert\Positive(message: "La quantité doit être supérieure à zéro.")]
    private ?float $quantite = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: "Le prix HT est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le prix HT doit être positif.")]
    private ?float $prixHt = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: "Le taux de TVA est obligatoire.")]
    #[Assert\Range(
        notInRangeMessage: "La TVA doit être comprise entre 0 et 100.",
        min: 0,
        max: 100
    )]
    private ?float $tva = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $montantTva = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: "Le total TTC est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le total TTC doit être positif.")]
    private ?float $totalTtc = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(
        notInRangeMessage: "La remise doit être comprise entre 0 et 100.",
        min: 0,
        max: 100
    )]
    private ?float $remise = null;

    #[ORM\ManyToOne(targetEntity: Facture::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Chaque ligne doit être liée à une facture.")]
    private ?Facture $facture = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $ordre = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $unite = 'kg';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getProduit(): ?string
    {
        return $this->produit;
    }

    public function setProduit(string $produit): self
    {
        $this->produit = $produit;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }

    public function setQuantite(float $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getPrixHt(): ?float
    {
        return $this->prixHt;
    }

    public function setPrixHt(float $prixHt): self
    {
        $this->prixHt = $prixHt;
        return $this;
    }

    public function getTva(): ?float
    {
        return $this->tva;
    }

    public function setTva(float $tva): self
    {
        $this->tva = $tva;
        return $this;
    }

    public function getMontantTva(): ?float
    {
        return $this->montantTva;
    }

    public function setMontantTva(?float $montantTva): self
    {
        $this->montantTva = $montantTva;
        return $this;
    }

    public function getTotalTtc(): ?float
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(float $totalTtc): self
    {
        $this->totalTtc = $totalTtc;
        return $this;
    }

    public function getRemise(): ?float
    {
        return $this->remise;
    }

    public function setRemise(?float $remise): self
    {
        $this->remise = $remise;
        return $this;
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

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): self
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): self
    {
        $this->unite = $unite;
        return $this;
    }
}

