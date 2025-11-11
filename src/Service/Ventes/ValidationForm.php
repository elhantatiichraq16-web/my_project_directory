<?php

namespace App\Service\Ventes;

use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ValidationForm
{
    private ValidationService $validationService;
    private GlobalService $globalService;
    private ?SessionInterface $session = null;
    private array $errors = [];

    public function __construct(
        ValidationService $validationService,
        GlobalService $globalService,
        RequestStack $requestStack
    ) {
        $this->validationService = $validationService;
        $this->globalService = $globalService;
        $this->session = $requestStack->getSession();
    }

    /**
     * ✅ Valide les données du formulaire de facture.
     *
     * @param array $data
     * @return bool True si valide, False sinon
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        // 🧩 Vérification des champs requis
        if (empty($data['reference'])) {
            $this->errors['reference'] = 'La référence de la facture est obligatoire.';
        }

        if (empty($data['tier'])) {
            $this->errors['tier'] = 'Le client est obligatoire.';
        }

        if (empty($data['date_creation'])) {
            $this->errors['date_creation'] = 'La date de création est obligatoire.';
        }

        if (empty($data['objet'])) {
            $this->errors['objet'] = "L'objet de la facture est obligatoire.";
        }

        // 🧩 Vérifie le format de la date
        if (!empty($data['date_creation'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['date_creation']);
            if (!$date || $date->format('Y-m-d') !== $data['date_creation']) {
                $this->errors['date_creation'] = 'Format de date invalide (attendu : YYYY-MM-DD).';
            }
        }

        // 🧩 Appel au ValidationService pour vérification complémentaire
        if (!$this->validationService->isValid($data)) {
            $this->errors = array_merge($this->errors, $this->validationService->getErrors());
        }

        return empty($this->errors);
    }

    /**
     * ✅ Retourne les erreurs du formulaire
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}






