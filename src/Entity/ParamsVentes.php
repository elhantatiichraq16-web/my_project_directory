<?php

namespace App\Entity;

use App\Repository\ParamsVentesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParamsVentesRepository::class)]
#[ORM\Table(name: "params_ventes")]
class ParamsVentes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 50, options: ["default" => "facture"])]
    private ?string $module = 'facture';

    #[ORM\Column(length: 255)]
    private ?string $cle = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    // 🔹 Champ ON/OFF
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $etat = null;

    // 🔹 Champ pour pourcentage/montant — correction ici
    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?string $valeur = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(length: 255)]
    private ?string $cle_labels = null;

    // 🔹 Nouveau champ pour le nom visible (titre)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    // =======================
    // 🔸 Getters & Setters
    // =======================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getModule(): ?string
    {
        return $this->module;
    }

    public function setModule(string $module): static
    {
        $this->module = $module;
        return $this;
    }

    public function getCle(): ?string
    {
        return $this->cle;
    }

    public function setCle(string $cle): static
    {
        $this->cle = $cle;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): static
    {
        $this->etat = $etat;
        return $this;
    }

    // 🟢 Champ valeur (stocké en string mais retourné en float)
    public function getValeur(): ?float
    {
        return $this->valeur !== null ? (float) $this->valeur : null;
    }

    public function setValeur($valeur): static
    {
        if ($valeur === null || $valeur === '') {
            $this->valeur = null;
        } else {
            $this->valeur = (string) $valeur;
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getCleLabels(): ?string
    {
        return $this->cle_labels;
    }

    public function setCleLabels(string $cle_labels): static
    {
        $this->cle_labels = $cle_labels;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }
}
