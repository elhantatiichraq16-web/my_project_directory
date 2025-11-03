<?php

namespace App\Entity;

use App\Repository\FactureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FactureRepository::class)]
#[ORM\Table(name: 'facture')]
class Facture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "La référence est obligatoire.")]
    private ?string $reference = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "L’objet de la facture est obligatoire.")]
    private ?string $objet = null;

    #[ORM\Column(type: 'string', length: 10)]
    #[Assert\NotBlank(message: "La devise est obligatoire.")]
    private ?string $devise = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: "L’état de la facture est obligatoire.")]
    private ?string $etat = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: "La date de création est obligatoire.")]
    #[Assert\Type(\DateTimeInterface::class, message: "La date de création doit être valide.")]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\Type(\DateTimeInterface::class, message: "La date d’échéance doit être valide.")]
    private ?\DateTimeInterface $dateEcheance = null;

    #[ORM\ManyToOne(targetEntity: Tiers::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le client doit être sélectionné.")]
    private ?Tiers $tier = null;

    /**
     * @var Collection<int, LigneFacture>
     */
    #[ORM\OneToMany(mappedBy: 'facture', targetEntity: LigneFacture::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid]
    private Collection $lignes;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $remiseGlobale = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $deleted = false;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $retenueSource = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $retenueGarantie = null;

    /**
     * @var Collection<int, FactureParam>
     */
    #[ORM\OneToMany(mappedBy: 'facture', targetEntity: FactureParam::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $valeurs;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->valeurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(string $objet): self
    {
        $this->objet = $objet;
        return $this;
    }

    public function getDevise(): ?string
    {
        return $this->devise;
    }

    public function setDevise(string $devise): self
    {
        $this->devise = $devise;
        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): self
    {
        $this->etat = $etat;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateEcheance(): ?\DateTimeInterface
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(?\DateTimeInterface $dateEcheance): self
    {
        $this->dateEcheance = $dateEcheance;
        return $this;
    }

    public function getTier(): ?Tiers
    {
        return $this->tier;
    }

    public function setTier(?Tiers $tier): self
    {
        $this->tier = $tier;
        return $this;
    }

    /**
     * @return Collection<int, LigneFacture>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneFacture $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setFacture($this);
        }
        return $this;
    }

    public function removeLigne(LigneFacture $ligne): self
    {
        if ($this->lignes->removeElement($ligne)) {
            if ($ligne->getFacture() === $this) {
                $ligne->setFacture(null);
            }
        }
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    public function getRemiseGlobale(): ?float
    {
        return $this->remiseGlobale !== null ? (float)$this->remiseGlobale : null;
    }

    public function setRemiseGlobale(?float $remiseGlobale): self
    {
        $this->remiseGlobale = $remiseGlobale !== null ? number_format($remiseGlobale, 2, '.', '') : null;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;
        return $this;
    }

    public function getRetenueSource(): ?float
    {
        return $this->retenueSource !== null ? (float)$this->retenueSource : null;
    }

    public function setRetenueSource(?float $retenueSource): self
    {
        $this->retenueSource = $retenueSource !== null ? number_format($retenueSource, 2, '.', '') : null;
        return $this;
    }

    public function getRetenueGarantie(): ?float
    {
        return $this->retenueGarantie !== null ? (float)$this->retenueGarantie : null;
    }

    public function setRetenueGarantie(?float $retenueGarantie): self
    {
        $this->retenueGarantie = $retenueGarantie !== null ? number_format($retenueGarantie, 2, '.', '') : null;
        return $this;
    }

    /**
     * @return Collection<int, FactureParam>
     */
    public function getValeurs(): Collection
    {
        return $this->valeurs;
    }

    public function addValeur(FactureParam $valeur): self
    {
        if (!$this->valeurs->contains($valeur)) {
            $this->valeurs->add($valeur);
            $valeur->setFacture($this);
        }
        return $this;
    }

    public function removeValeur(FactureParam $valeur): self
    {
        if ($this->valeurs->removeElement($valeur)) {
            if ($valeur->getFacture() === $this) {
                $valeur->setFacture(null);
            }
        }
        return $this;
    }

    public function getTotalHt(): float
    {
        $total = 0.0;

        foreach ($this->lignes as $ligne) {
            $quantite = (float)($ligne->getQuantite() ?? 0);
            $prixUnitaire = (float)($ligne->getPrixHt() ?? 0);
            $total += $quantite * $prixUnitaire;
        }

        $remise = $this->getRemiseGlobale();
        if ($remise !== null) {
            $total -= $remise;
        }

        return round(max($total, 0.0), 2);
    }

    public function getTotalTtc(): float
    {
        $total = 0.0;

        foreach ($this->lignes as $ligne) {
            $lineTotal = $ligne->getTotalTtc();
            if ($lineTotal !== null) {
                $total += (float)$lineTotal;
                continue;
            }

            $quantite = (float)($ligne->getQuantite() ?? 0);
            $prixUnitaire = (float)($ligne->getPrixHt() ?? 0);
            $tva = (float)($ligne->getTva() ?? 0);
            $total += $quantite * $prixUnitaire * (1 + ($tva / 100));
        }

        $remise = $this->getRemiseGlobale();
        if ($remise !== null) {
            $total -= $remise;
        }

        return round(max($total, 0.0), 2);
    }

    public function getNetAPayer(): float
    {
        $totalTtc = $this->getTotalTtc();

        $retenueSource = $this->getRetenueSource();
        if ($retenueSource !== null) {
            $totalTtc -= $totalTtc * ($retenueSource / 100);
        }

        $retenueGarantie = $this->getRetenueGarantie();
        if ($retenueGarantie !== null) {
            $totalTtc -= $totalTtc * ($retenueGarantie / 100);
        }

        return round(max($totalTtc, 0.0), 2);
    }

    public function getStatut(): ?string
    {
        return $this->etat;
    }

    public function setStatut(string $statut): self
    {
        $this->etat = $statut;
        return $this;
    }
    #[ORM\Column(type: 'float', nullable: true)]
private ?float $totalHT = null;

#[ORM\Column(type: 'float', nullable: true)]
private ?float $totalTVA = null;

#[ORM\Column(type: 'float', nullable: true)]
private ?float $totalTTC = null;

public function getTotalHTStored(): ?float
{
    return $this->totalHT;
}

public function setTotalHT(?float $totalHT): self
{
    $this->totalHT = $totalHT;
    return $this;
}

public function getTotalTVAStored(): ?float
{
    return $this->totalTVA;
}

public function setTotalTVA(?float $totalTVA): self
{
    $this->totalTVA = $totalTVA;
    return $this;
}

public function getTotalTTCStored(): ?float
{
    return $this->totalTTC;
}

public function setTotalTTC(?float $totalTTC): self
{
    $this->totalTTC = $totalTTC;
    return $this;
}

}
