<?php

namespace App\Entity;

use App\Repository\EmailLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
class EmailLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $toAddress = null;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: 'text')]
    private ?string $body = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\ManyToOne(targetEntity: Facture::class, inversedBy: 'emailLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Facture $facture = null;

    // ─── Getters & Setters ──────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToAddress(): ?string
    {
        return $this->toAddress;
    }

    public function setToAddress(string $toAddress): self
    {
        $this->toAddress = $toAddress;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeInterface $sentAt): self
    {
        $this->sentAt = $sentAt;
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

    // ─── Méthodes Utilitaires ──────────────────────────────────────────

    /**
     * Retourne la référence de la facture liée ou "(Aucune)" si aucune.
     */
    public function getFactureReference(): string
    {
        return $this->facture ? $this->facture->getReference() : '(Aucune)';
    }

    /**
     * Renvoie un petit résumé utile pour le debug ou l’affichage.
     */
    public function getSummary(): string
    {
        return sprintf(
            '[%s] %s → %s (%s)',
            $this->sentAt ? $this->sentAt->format('Y-m-d H:i') : 'Date inconnue',
            $this->subject ?? '(Sans objet)',
            $this->toAddress ?? '(Sans destinataire)',
            strtoupper($this->status ?? '?')
        );
    }

    /**
     * Permet d’afficher l’objet directement sous forme de texte (ex: dans EasyAdmin).
     */
    public function __toString(): string
    {
        return $this->getSummary();
    }
}
