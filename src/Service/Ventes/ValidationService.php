<?php

namespace App\Service\Ventes;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ValidationService
{
    private ?SessionInterface $session = null;
    private array $errors = [];

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    public function isValid(array $data): bool
    {
        $this->errors = [];

        // 🔹 Référence obligatoire
        if (empty($data['reference'])) {
            $this->errors['reference'] = 'La référence est obligatoire.';
        }

        // 🔹 Client obligatoire
        if (empty($data['tier'])) {
            $this->errors['tier'] = 'Le client doit être sélectionné.';
        }

        // 🔹 Date de création obligatoire et format valide
        if (empty($data['date_creation'])) {
            $this->errors['date_creation'] = 'La date de création est obligatoire.';
        } elseif (!$this->isValidDate($data['date_creation'])) {
            $this->errors['date_creation'] = 'Format de date invalide (attendu : YYYY-MM-DD).';
        }

        // 🔹 Objet obligatoire
        if (empty($data['objet'])) {
            $this->errors['objet'] = 'L’objet de la facture est obligatoire.';
        }

        // 🔹 Vérifie la date d’échéance si elle est renseignée
        if (!empty($data['date_echeance']) && !$this->isValidDate($data['date_echeance'])) {
            $this->errors['date_echeance'] = 'Format de date d’échéance invalide.';
        }

        // ✅ Retourne false si des erreurs existent
        return empty($this->errors);
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
