<?php

namespace App\Controller;

use App\Entity\EmailLog;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Facture;
use App\Entity\LigneFacture;
use App\Entity\ParamsVentes;
use App\Repository\TiersRepository;
use App\Service\Ventes\ValidationForm;
use App\Repository\FactureRepository;
use App\Repository\ParamsVentesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FactureController extends AbstractController
{
    private SessionInterface $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    #[Route('/facture', name: 'app_facture')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_factures_list');
    }

    #[Route('/facture/create', name: 'app_facture_create')]
    public function create(
        Request $request,
        TiersRepository $tiersRepo,
        EntityManagerInterface $entityManager,
        ParamsVentesRepository $paramsVentesRepository,
        MailerInterface $mailer,
    ): Response {
        if ($request->isMethod('POST')) {
            try {
                $data = $request->request->all();
                
                $errors = [];
                
                if (empty($data['reference'])) {
                    $errors['reference'] = 'La référence est obligatoire';
                }
                if (empty($data['tier']) || $data['tier'] === '') {
                    $errors['tier'] = 'Le client est obligatoire';
                }
                if (empty($data['objet'])) {
                    $errors['objet'] = 'L\'objet est obligatoire';
                }
                if (empty($data['date_creation'])) {
                    $errors['date_creation'] = 'La date de création est obligatoire';
                }
                
                if (!empty($errors)) {
                    return new JsonResponse([
                        'success' => false,
                        'errors' => $errors,
                    ], 400);
                }

                $facture = new Facture();
                $facture->setReference($data['reference']);
                $facture->setObjet($data['objet'] ?? '');
                $facture->setDevise($data['devise'] ?? 'MAD');
                $facture->setEtat($data['etat'] ?? 'Brouillon');
                $facture->setNotes($data['notes'] ?? '');

                $facture->setDateCreation(
                    !empty($data['date_creation'])
                        ? new \DateTime($data['date_creation'])
                        : new \DateTime()
                );

                if (!empty($data['date_echeance'])) {
                    $facture->setDateEcheance(new \DateTime($data['date_echeance']));
                }

                if (!empty($data['tier'])) {
                    $tier = $tiersRepo->find($data['tier']);
                    if ($tier) {
                        $facture->setTier($tier);
                    }
                }

                $facture->setRetenueSource(isset($data['retenue_source_globale']) ? (float)$data['retenue_source_globale'] : null);
                $facture->setRetenueGarantie(isset($data['retenue_garantie_globale']) ? (float)$data['retenue_garantie_globale'] : null);
                $facture->setRemiseGlobale(isset($data['remise_globale']) ? (float)$data['remise_globale'] : null);

                // ✅ Persist et flush la facture UNE SEULE FOIS (elle a besoin d'un ID pour les lignes)
                $entityManager->persist($facture);
                $entityManager->flush();

                $pdfPath = $this->savePdfLocally($facture, $paramsVentesRepository);

                $clientEmail = $facture->getTier()?->getEmail() ?? 'client@example.com';

                $email = (new Email())
                    ->from($this->getParameter('mailer_from'))
                    ->to($clientEmail)
                    ->subject('Nouvelle facture ' . $facture->getReference())
                    ->text("Bonjour,\n\nVeuillez trouver votre facture " . $facture->getReference() . " ci-jointe.\nMerci de votre confiance.");

                if (file_exists($pdfPath)) {
                    $email->attachFromPath($pdfPath);
                }

                try {
                    $mailer->send($email);
                } catch (\Throwable $mailError) {
                    // L'email échoue mais ne bloque pas la création de la facture
                    error_log('Email error: ' . $mailError->getMessage());
                }

                // ✅ Traiter les lignes produits avec validation complète
                if (!empty($data['produits']) && is_array($data['produits'])) {
                    $ordre = 1;
                    foreach ($data['produits'] as $produitData) {
                        // ✅ Validation : au moins le produit ou la référence doit être renseignée
                        if (empty($produitData['produit']) && empty($produitData['reference'])) {
                            continue;
                        }

                        // ✅ Validation des champs obligatoires
                        $quantite = (float)($produitData['quantite'] ?? 1);
                        $prixHt = (float)($produitData['prix_ht'] ?? 0);
                        $tva = (float)($produitData['tva'] ?? 20);
                        $remise = (float)($produitData['remise'] ?? 0);

                        // ✅ Validation des valeurs
                        if ($quantite <= 0) {
                            throw new \Exception('La quantité doit être supérieure à 0 pour le produit ' . ($produitData['produit'] ?? $produitData['reference']));
                        }
                        if ($prixHt < 0) {
                            throw new \Exception('Le prix HT ne peut pas être négatif pour le produit ' . ($produitData['produit'] ?? $produitData['reference']));
                        }
                        if ($tva < 0 || $tva > 100) {
                            throw new \Exception('La TVA doit être comprise entre 0 et 100 pour le produit ' . ($produitData['produit'] ?? $produitData['reference']));
                        }
                        if ($remise < 0 || $remise > 100) {
                            throw new \Exception('La remise doit être comprise entre 0 et 100 pour le produit ' . ($produitData['produit'] ?? $produitData['reference']));
                        }

                        $ligne = new LigneFacture();
                        $ligne->setFacture($facture);
                        $ligne->setReference($produitData['reference'] ?? '');
                        $ligne->setProduit($produitData['produit'] ?? '');
                        $ligne->setQuantite($quantite);
                        $ligne->setPrixHt($prixHt);
                        $ligne->setTva($tva);
                        $ligne->setRemise($remise);
                        $ligne->setOrdre($ordre++);

                        // ✅ Calcul du total TTC et montant TVA
                        $htLigne = $quantite * $prixHt;
                        $montantRemise = $htLigne * ($remise / 100);
                        $htNet = $htLigne - $montantRemise;
                        $montantTva = $htNet * ($tva / 100);
                        $totalTtc = $htNet + $montantTva;

                        $ligne->setMontantTva($montantTva);
                        $ligne->setTotalTtc($totalTtc);

                        // ✅ Ajouter les champs optionnels
                        if (!empty($produitData['description'])) {
                            $ligne->setDescription($produitData['description']);
                        }

                        if (!empty($produitData['unite'])) {
                            $ligne->setUnite($produitData['unite']);
                        }

                        $facture->addLigne($ligne);
                        $entityManager->persist($ligne);
                    }

                    // ✅ Flush une seule fois après toutes les lignes
                    $entityManager->flush();
                }

                return new JsonResponse([
                    'success' => true, 
                    'id' => $facture->getId()
                ]);

            } catch (\Throwable $e) {
                error_log('Error in facture create: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                
                return new JsonResponse([
                    'success' => false, 
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        $tiers = $tiersRepo->findAll();
        $factureParams = $this->getAllFactureParams($paramsVentesRepository);
        $modePaiement = ['Espèces', 'Chèque', 'Virement bancaire', 'Carte de crédit'];
        $devises = ['MAD', 'EUR', 'USD'];

        return $this->render('facture/create.html.twig', [
            'tiers' => $tiers,
            'modePaiement' => $modePaiement,
            'devises' => $devises,
            'factureParams' => $factureParams,
        ]);
    }

    #[Route('/factures', name: 'app_factures_list')]
    public function facturesList(FactureRepository $factureRepository, TiersRepository $tiersRepo): Response
    {
        $factures = $factureRepository->findAll();
        $tiers = $tiersRepo->findAll();
        $devises = ['MAD', 'EUR', 'USD'];
        $modePaiement = ['Espèces', 'Chèque', 'Virement'];

        return $this->render('facture/list.html.twig', [
            'factures' => $factures,
            'tiers' => $tiers,
            'devises' => $devises,
            'modePaiement' => $modePaiement,
        ]);
    }

    #[Route('/facture/{id}', name: 'app_facture_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Facture $facture, ParamsVentesRepository $paramsVentesRepository): Response
    {
        $showRetenueSource = $this->shouldDisplayRetenueSource($paramsVentesRepository);
        $lignes = $this->getLignesData($facture);

        $totalHt = 0;
        $totalTva = 0;
        $totalTtc = 0;
        foreach ($lignes as $ln) {
            $totalHt += $ln['total_ht'];
            $totalTva += $ln['total_tva'];
            $totalTtc += $ln['total_ttc'];
        }

        return $this->render('facture/show.html.twig', [
            'facture' => $facture,
            'lignes' => $lignes,
            'totaux' => [
                'total_ht' => $totalHt,
                'total_tva' => $totalTva,
                'total_ttc' => $totalTtc,
            ],
            'showRetenueSource' => $showRetenueSource,
            'factureParams' => $this->getAllFactureParams($paramsVentesRepository),
        ]);
    }

    #[Route('/facture/{id}/edit', name: 'app_facture_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Facture $facture,
        TiersRepository $tiersRepo,
        EntityManagerInterface $entityManager,
        ParamsVentesRepository $paramsVentesRepository
    ): Response {
        $tiers = $tiersRepo->findAll();
        $errors = [];
        $showRetenueSource = $this->shouldDisplayRetenueSource($paramsVentesRepository);
        $showRetenueGarantie = $this->shouldDisplayRetenueGarantie($paramsVentesRepository);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            if (!$this->isCsrfTokenValid('edit_facture_' . $facture->getId(), $data['_csrf_token'] ?? '')) {
                $errors[] = 'Le formulaire a expiré, veuillez réessayer.';
            }

            if (empty($errors)) {
                $facture->setReference($data['reference']);
                $facture->setObjet($data['objet'] ?? $facture->getObjet());
                $facture->setRetenueSource(isset($data['retenue_source_globale']) ? (float) $data['retenue_source_globale'] : $facture->getRetenueSource());
                $facture->setRetenueGarantie(isset($data['retenue_garantie_globale']) ? (float) $data['retenue_garantie_globale'] : $facture->getRetenueGarantie());
                $facture->setRemiseGlobale(isset($data['remise_globale']) ? (float) $data['remise_globale'] : $facture->getRemiseGlobale());
                $facture->setDevise($data['devise'] ?? $facture->getDevise() ?? 'MAD');
                $facture->setEtat($data['etat'] ?? $facture->getEtat() ?? 'Brouillon');
                $facture->setNotes($data['notes'] ?? $facture->getNotes());

                $facture->setDateCreation(
                    !empty($data['date_creation'])
                        ? new \DateTime($data['date_creation'])
                        : $facture->getDateCreation()
                );

                $facture->setDateEcheance(
                    !empty($data['date_echeance'])
                        ? new \DateTime($data['date_echeance'])
                        : null
                );

                $selectedTier = !empty($data['tier']) ? $tiersRepo->find($data['tier']) : null;
                if ($selectedTier) {
                    $facture->setTier($selectedTier);
                }

                $facture->setDateModification(new \DateTimeImmutable());

                // ✅ Supprimer les anciennes lignes
                foreach ($facture->getLignes() as $ligneExistante) {
                    $facture->removeLigne($ligneExistante);
                    $entityManager->remove($ligneExistante);
                }
                $entityManager->flush();

                // ✅ Créer les nouvelles lignes avec validation complète
                if (!empty($data['produits']) && is_array($data['produits'])) {
                    $ordre = 1;
                    foreach ($data['produits'] as $produitData) {
                        // ✅ Validation : au moins le produit ou la référence doit être renseignée
                        if (empty($produitData['produit']) && empty($produitData['reference'])) {
                            continue;
                        }

                        // ✅ Validation des champs obligatoires
                        $quantite = (float)($produitData['quantite'] ?? 1);
                        $prixHt = (float)($produitData['prix_ht'] ?? 0);
                        $tva = (float)($produitData['tva'] ?? 20);
                        $remise = (float)($produitData['remise'] ?? 0);

                        // ✅ Validation des valeurs
                        if ($quantite <= 0) {
                            throw new \Exception('La quantité doit être supérieure à 0 pour le produit ' . ($produitData['produit'] ?? $produitData['reference']));
                        }
                        if ($prixHt < 0) {
                            throw new \Exception('Le prix HT ne peut pas être négatif pour le produit ' . ($produitData['produit'] ?? $produitData['reference']));
                        }
                        if ($tva < 0 || $tva > 100) {
                            throw new \Exception('La TVA doit être comprise entre 0 et 100 pour le produit ' . ($produitData['produit'] ?? $produitData['reference']));
                        }
                        if ($remise < 0 || $remise > 100) {
                            throw new \Exception('La remise doit être comprise entre 0 et 100 pour le produit ' . ($produitData['produit'] ?? $produitData['reference']));
                        }

                        $ligne = new LigneFacture();
                        $ligne->setFacture($facture);
                        $ligne->setReference($produitData['reference'] ?? '');
                        $ligne->setProduit($produitData['produit'] ?? '');
                        $ligne->setQuantite($quantite);
                        $ligne->setPrixHt($prixHt);
                        $ligne->setTva($tva);
                        $ligne->setRemise($remise);
                        $ligne->setOrdre($ordre++);

                        // ✅ Calcul du total TTC et montant TVA
                        $htLigne = $quantite * $prixHt;
                        $montantRemise = $htLigne * ($remise / 100);
                        $htNet = $htLigne - $montantRemise;
                        $montantTva = $htNet * ($tva / 100);
                        $totalTtc = $htNet + $montantTva;

                        $ligne->setMontantTva($montantTva);
                        $ligne->setTotalTtc($totalTtc);

                        // ✅ Ajouter les champs optionnels
                        if (!empty($produitData['description'])) {
                            $ligne->setDescription($produitData['description']);
                        }

                        if (!empty($produitData['unite'])) {
                            $ligne->setUnite($produitData['unite']);
                        }

                        $facture->addLigne($ligne);
                        $entityManager->persist($ligne);
                    }
                }

                $entityManager->persist($facture);
                $entityManager->flush();
                $entityManager->refresh($facture);

                $this->addFlash('success', '✅ Facture mise à jour avec succès !');
                return $this->redirectToRoute('app_factures_list');
            }
        }

        $devises = ['MAD', 'EUR', 'USD'];

        // ✅ Calculer les totaux en utilisant les montants stockés dans les lignes
        $totalHt = 0;
        $totalRemise = 0;
        $totalTva = 0;
        $totalTtc = 0;

        foreach ($facture->getLignes() as $ligne) {
            $quantite = (float) $ligne->getQuantite();
            $prixHt = (float) $ligne->getPrixHt();
            $remise = (float) ($ligne->getRemise() ?? 0);
            $montantTva = (float) ($ligne->getMontantTva() ?? 0);
            $totalTtc_ligne = (float) $ligne->getTotalTtc();

            $htLigne = $quantite * $prixHt;
            $montantRemise = $htLigne * ($remise / 100);
            $htNet = $htLigne - $montantRemise;

            $totalHt += $htLigne;
            $totalRemise += $montantRemise;
            $totalTva += $montantTva;
            $totalTtc += $totalTtc_ligne;
        }

        $totaux = [
            'total_ht' => $totalHt,
            'total_remise' => $totalRemise,
            'total_tva' => $totalTva,
            'total_ttc' => $totalTtc,
        ];

        return $this->render('facture/edit.html.twig', [
            'facture' => $facture,
            'tiers' => $tiers,
            'errors' => $errors,
            'showRetenueSource' => $showRetenueSource,
            'showRetenueGarantie' => $showRetenueGarantie,
            'devises' => $devises,
            'factureParams' => $this->getAllFactureParams($paramsVentesRepository),
            'totaux' => $totaux,
            'lignes' => $facture->getLignes(),
        ]);
    }

    #[Route('/facture/{id}/pdf', name: 'app_facture_pdf')]
    public function generatePdf(Facture $facture, ParamsVentesRepository $paramsVentesRepository): Response
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);
        $factureParams = $this->getAllFactureParams($paramsVentesRepository);

        $html = $this->renderView('facture/pdf.html.twig', [
            'facture' => $facture,
            'factureParams' => $factureParams,
            'showRetenueSource' => $this->shouldDisplayRetenueSource($paramsVentesRepository),
            'showRetenueGarantie' => $this->shouldDisplayRetenueGarantie($paramsVentesRepository),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'facture-' . $facture->getReference() . '.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }

    #[Route('/facture/{id}/send-email', name: 'facture_send_email', methods: ['POST'])]
public function sendEmailUnified(
    int $id,
    Request $request,
    EntityManagerInterface $em,
    MailerInterface $mailer,
    ParamsVentesRepository $paramsVentesRepository
): JsonResponse {
    try {
        $data = json_decode($request->getContent(), true);

        $destinataire = $data['destinataire'] ?? $data['email_to'] ?? null;
        $objet = $data['objet'] ?? $data['email_subject'] ?? null;
        $message = $data['message'] ?? $data['email_body'] ?? '';

        $facture = $em->getRepository(Facture::class)->find($id);
        if (!$facture) {
            return new JsonResponse([
                'success' => false,
                'message' => '❌ Facture introuvable.'
            ], 404);
        }

        // Si aucun destinataire n’est précisé, on prend celui du client
        if (empty($destinataire)) {
            $tier = $facture->getTier();
            if (!$tier || !$tier->getEmail()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => '❌ Aucun email défini pour ce client.'
                ], 400);
            }
            $destinataire = $tier->getEmail();
        }

        // Validation de l’email
        if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'success' => false,
                'message' => '❌ Adresse email invalide.'
            ], 400);
        }

        // Sujet par défaut si manquant
        if (empty($objet)) {
            $objet = 'Votre facture ' . $facture->getReference();
        }

        // Génération du PDF
        $html = $this->renderView('facture/pdf.html.twig', [
            'facture' => $facture,
            'factureParams' => $this->getAllFactureParams($paramsVentesRepository),
            'showRetenueSource' => $this->shouldDisplayRetenueSource($paramsVentesRepository),
            'showRetenueGarantie' => $this->shouldDisplayRetenueGarantie($paramsVentesRepository),
        ]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        // Corps du mail
        $htmlBody = nl2br(htmlspecialchars($message));

        // Préparation email
        $email = (new \Symfony\Component\Mime\Email())
            ->from($this->getParameter('mailer_from'))
            ->to($destinataire)
            ->subject($objet)
            ->html($htmlBody)
            ->text($message)
            ->attach($pdfOutput, 'facture-' . $facture->getReference() . '.pdf', 'application/pdf');

        // Création du log avant envoi
        $emailLog = new \App\Entity\EmailLog();
        $emailLog
            ->setFacture($facture)
            ->setToAddress($destinataire)
            ->setSubject($objet)
            ->setBody($message)
            ->setSentAt(new \DateTime());

        try {
            $mailer->send($email);
            $emailLog->setStatus('envoyé');
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $emailLog->setStatus('échec');
            $emailLog->setErrorMessage($e->getMessage());
        }

        // Sauvegarde du log
        $em->persist($emailLog);
        $em->flush();

        if ($emailLog->getStatus() === 'échec') {
            return new JsonResponse([
                'success' => false,
                'message' => '❌ Erreur d\'envoi : ' . $emailLog->getErrorMessage()
            ], 500);
        }

        return new JsonResponse([
            'success' => true,
            'message' => '✅ Facture envoyée à ' . $destinataire
        ]);

    } catch (\Throwable $e) {
        return new JsonResponse([
            'success' => false,
            'message' => '❌ Erreur interne : ' . $e->getMessage()
        ], 500);
    }
}

#[Route('/facture/{id}/email-history', name: 'facture_email_history', methods: ['GET'])]
public function getEmailHistory(int $id, EntityManagerInterface $em): JsonResponse
{
    try {
        // 🔍 Récupérer la facture
        $facture = $em->getRepository(Facture::class)->find($id);
        if (!$facture) {
            return new JsonResponse(['error' => 'Facture non trouvée'], 404);
        }

        // 📧 Récupérer uniquement les e-mails envoyés pour cette facture
        $emails = $em->getRepository(EmailLog::class)->findBy(
            [
                'facture' => $facture,
                'status' => 'envoyé'  // ← filtre ici
            ],
            ['sentAt' => 'DESC']
        );

        // 🧾 Transformer les objets en tableau lisible
        $result = array_map(function ($email) {
            return [
                'id' => $email->getId(),
                'destinataire' => $email->getToAddress() ?? '(Non spécifié)',
                'objet' => $email->getSubject() ?? '(Sans objet)',
                'date' => $email->getSentAt()
                    ? $email->getSentAt()->format('Y-m-d H:i:s')
                    : '(Non envoyée)',
                'statut' => $email->getStatus() ?? 'inconnu',
            ];
        }, $emails);

        return new JsonResponse($result);

    } catch (\Throwable $e) {
        return new JsonResponse(['error' => $e->getMessage()], 500);
    }
}

#[Route('/facture/email-history', name: 'facture_email_history_global', methods: ['GET'])]
public function getGlobalEmailHistory(EntityManagerInterface $em): JsonResponse
{
    try {
        $emails = $em->getRepository(EmailLog::class)->findBy([], ['sentAt' => 'DESC'], 50);

        $result = array_map(function (EmailLog $email) {
            return [
                'id' => $email->getId(),
                'reference' => $email->getFacture() 
                    ? $email->getFacture()->getReference() 
                    : '(Aucune)',
                'destinataire' => $email->getToAddress() ?? '(Non spécifié)',
                'objet' => $email->getSubject() ?? '(Sans objet)',
                'date' => $email->getSentAt() 
                    ? $email->getSentAt()->format('Y-m-d H:i:s') 
                    : '(Non envoyée)',
                'statut' => $email->getStatus() ?? 'inconnu',
            ];
        }, $emails);

        return new JsonResponse($result);
    } catch (\Throwable $e) {
        return new JsonResponse(['error' => $e->getMessage()], 500);
    }
}


#[Route('/facture/email-detail/{id}', name: 'facture_email_detail', methods: ['GET'])]
public function getEmailDetail(int $id, EntityManagerInterface $em): JsonResponse
{
    try {
        $email = $em->getRepository(EmailLog::class)->find($id);
        
        if (!$email) {
            return new JsonResponse(['error' => 'Email non trouvé'], 404);
        }

        // Récupérer la facture liée à cet email
        $facture = $email->getFacture();
        $pdfUrl = null;
        $factureId = null;
        $factureReference = '(Aucune)';

        if ($facture) {
            $factureId = $facture->getId();
            $factureReference = $facture->getReference();
            $pdfUrl = $this->generateUrl('app_facture_pdf', ['id' => $factureId]);
        }

        // ✅ On renvoie tout le nécessaire pour le modal
        return new JsonResponse([
            'id' => $email->getId(),
            'factureId' => $factureId,
            'reference' => $factureReference,
            'destinataire' => $email->getToAddress(),
            'objet' => $email->getSubject(),
            'message' => $email->getBody(),
            'date' => $email->getSentAt() ? $email->getSentAt()->format('Y-m-d H:i:s') : null,
            'statut' => $email->getStatus(),
            'pdfUrl' => $pdfUrl, // 🔗 Lien direct vers la facture PDF
        ]);
    } catch (\Throwable $e) {
        return new JsonResponse(['error' => $e->getMessage()], 500);
    }
}


 
   #[Route('/facture/{id}/print', name: 'app_facture_print')]
    public function printFacture(Facture $facture, ParamsVentesRepository $paramsVentesRepository): Response
    {
        return $this->generatePdf($facture, $paramsVentesRepository);
    }

    #[Route('/facture/get/list', name: 'facture_list_data')]
    public function getListData(Request $request, FactureRepository $factureRepo): JsonResponse
    {
        try {
            $startRow = (int)$request->query->get('startRow', 0);
            $endRow = (int)$request->query->get('endRow', 20);
            $filterModel = json_decode((string)$request->query->get('filterModel', '{}'), true);
            $etat = $request->query->get('etat', 1);
            $search = trim((string) $request->query->get('search', ''));

            $qb = $factureRepo->createQueryBuilder('f')
                ->leftJoin('f.tier', 't');

            if ((int)$etat === 1) {
                $qb->andWhere('f.deleted = false OR f.deleted IS NULL');
            } else {
                $qb->andWhere('f.deleted = true');
            }

            if (!empty($filterModel['reference'])) {
                $qb->andWhere('f.reference LIKE :ref')
                   ->setParameter('ref', '%'.$filterModel['reference']['filter'].'%');
            }

            if ($search !== '') {
                $searchLower = mb_strtolower($search, 'UTF-8');

                $orConditions = $qb->expr()->orX(
                    $qb->expr()->like('LOWER(f.reference)', ':search'),
                    $qb->expr()->like('LOWER(f.notes)', ':search'),
                    $qb->expr()->like('LOWER(t.nom)', ':search')
                );

                $searchDate = $this->parseSearchDate($search);
                if ($searchDate) {
                    $dateStart = (clone $searchDate)->setTime(0, 0, 0);
                    $dateEnd = (clone $searchDate)->setTime(23, 59, 59);

                    $orConditions->add($qb->expr()->between('f.dateCreation', ':dateStart', ':dateEnd'));
                    $orConditions->add($qb->expr()->between('f.dateEcheance', ':dateStart', ':dateEnd'));

                    $qb->setParameter('dateStart', $dateStart);
                    $qb->setParameter('dateEnd', $dateEnd);
                }

                $qb->andWhere($orConditions)
                   ->setParameter('search', '%'.$searchLower.'%');
            }

            $qb->setFirstResult($startRow)->setMaxResults($endRow - $startRow);
            $factures = $qb->getQuery()->getResult();

            $rows = [];
            foreach ($factures as $f) {
                $rows[] = [
                    'id' => $f->getId(),
                    'reference' => $f->getReference(),
                    'tier' => $f->getTier() ? json_encode([
                        'id' => $f->getTier()->getId(),
                        'name' => $f->getTier()->getNom(),
                        'email' => $f->getTier()->getEmail()
                    ]) : null,
                    'statut' => $f->getEtat(),
                    'etat' => $f->getEtat(),
                    'date_creation' => $f->getDateCreation() ? $f->getDateCreation()->format('Y-m-d') : null,
                    'date_validite' => $f->getDateEcheance() ? $f->getDateEcheance()->format('Y-m-d') : null,
                    'totalHt' => $this->calculateTotalHT($f),
                    'totalTTC' => $this->calculateTotalTTC($f),
                    'tva' => $this->calculateTVA($f),
                    'devise' => $f->getDevise(),
                    'notes' => $f->getNotes(),
                    'date_modification' => $f->getDateModification() ? $f->getDateModification()->format('Y-m-d') : null,
                    'remiseGlobale' => $this->getRemiseGlobale($f),
                    'Actions' => $f->getId(),
                ];
            }

            return new JsonResponse([
                'rows' => $rows,
                'lastRow' => count($rows) < ($endRow - $startRow) ? $startRow + count($rows) : -1
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/general/table/get-preferences/{table}', name: 'get_table_preferences', methods: ['GET'])]
    public function getTablePreferences(string $table): JsonResponse
    {
        return new JsonResponse([]);
    }

    #[Route('/facture/parametrage', name: 'app_facture_parametrage', methods: ['GET'])]
    public function parametrage(ParamsVentesRepository $paramsVentesRepository): Response
    {
        $params = $paramsVentesRepository->findBy(['module' => 'facture']);

        return $this->render('facture/parametrage.html.twig', [
            'params' => $params,
            'factureParams' => $this->getAllFactureParams($paramsVentesRepository),
        ]);
    }

    #[Route('/facture/toggle/param', name: 'app_facture_toggle_param', methods: ['POST'])]
    public function toggleParam(
        Request $request,
        ParamsVentesRepository $paramsVentesRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true) ?? [];

        if (!$this->isCsrfTokenValid('toggle_param', $payload['_token'] ?? '')) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $cle = $payload['cle'] ?? null;
        $state = strtolower(trim((string) ($payload['state'] ?? 'off')));

        if (!$cle) {
            return new JsonResponse(['success' => false, 'message' => 'Clé du paramètre manquante.'], 400);
        }

        $normalizedState = in_array($state, ['on', '1', 'true', 'enabled', 'oui', 'yes'], true) ? 'on' : 'off';

        $param = $paramsVentesRepository->findOneBy([
            'module' => 'facture',
            'cle' => $cle,
        ]);

        if ($param === null) {
            $param = new ParamsVentes();
            $param->setModule('facture');
            $param->setCle($cle);
            $param->setType('switch');
            $param->setCleLabels($cle);
            $param->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($param);
        }

        $param->setEtat($normalizedState);
        $param->setValeur(null);
        $param->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'cle' => $cle, 'etat' => $normalizedState]);
    }

    #[Route('/facture/delete/{id}', name: 'facture_delete', methods: ['DELETE'])]
    public function deleteFacture(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $facture = $em->getRepository(Facture::class)->find($id);

        if (!$facture) {
            return new JsonResponse(['message' => 'Facture non trouvée'], 404);
        }

        $definitive = $request->query->getBoolean('definitive', false);

        if ($definitive) {
            $em->remove($facture);
            $em->flush();
            return new JsonResponse(['message' => 'Facture supprimée définitivement']);
        }

        if (method_exists($facture, 'setDeleted')) {
            $facture->setDeleted(true);
        } elseif (method_exists($facture, 'setEtat')) {
            $facture->setEtat('Supprimée');
        }

        $em->flush();

        return new JsonResponse(['message' => 'Facture supprimée avec succès']);
    }

    // MÉTHODES PRIVÉES

    private function shouldDisplayRetenueSource(ParamsVentesRepository $paramsVentesRepository): bool
    {
        return $this->getParamToggleState($paramsVentesRepository, 'facture', 'retenue_source_actif');
    }

    private function shouldDisplayRetenueGarantie(ParamsVentesRepository $paramsVentesRepository): bool
    {
        return $this->getParamToggleState($paramsVentesRepository, 'facture', 'retenue_garantie_actif');
    }

    private function getParamToggleState(ParamsVentesRepository $paramsVentesRepository, string $module, string $key): bool
    {
        $param = $paramsVentesRepository->findOneBy([
            'module' => $module,
            'cle' => $key,
        ]);

        if ($param === null) {
            return false;
        }

        $value = $param->getEtat();
        if ($value === null || $value === '') {
            $value = $param->getValeur();
        }

        if ($value === null) {
            return false;
        }

        return in_array(strtolower(trim((string) $value)), ['on', '1', 'true', 'enabled', 'oui', 'yes'], true);
    }

    private function parseSearchDate(string $search): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $search) ?: \DateTimeImmutable::createFromFormat('d/m/Y', $search);

        if ($date instanceof \DateTimeImmutable) {
            $errors = \DateTimeImmutable::getLastErrors();
            if (empty($errors['warning_count']) && empty($errors['error_count'])) {
                return $date;
            }
        }

        return null;
    }

    private function calculateTotalHT(Facture $facture): float
    {
        $total = 0;
        foreach ($facture->getLignes() as $ligne) {
            $total += $ligne->getQuantite() * $ligne->getPrixHt();
        }
        return $total;
    }

    private function calculateTVA(Facture $facture): float
    {
        $tva = 0;
        foreach ($facture->getLignes() as $ligne) {
            $ht = $ligne->getQuantite() * $ligne->getPrixHt();
            $tva += $ht * ($ligne->getTva() / 100);
        }
        return $tva;
    }

    private function calculateTotalTTC(Facture $facture): float
    {
        $total = $this->calculateTotalHT($facture) + $this->calculateTVA($facture);

        if ($facture->getRetenueSource()) {
            $total -= $facture->getRetenueSource();
        }
        if ($facture->getRetenueGarantie()) {
            $total -= $facture->getRetenueGarantie();
        }

        return $total;
    }

    private function getRemiseGlobale(Facture $facture): float
    {
        return $facture->getRemiseGlobale() ?? 0;
    }

    private function getLignesData(Facture $facture): array
    {
        $result = [];
        foreach ($facture->getLignes() as $ligne) {
            $quantite = (float) $ligne->getQuantite();
            $prixHt = (float) $ligne->getPrixHt();
            $remisePct = (float) ($ligne->getRemise() ?? 0);
            $tvaPct = (float) ($ligne->getTva() ?? 0);

            $totalHtAvantRemise = $quantite * $prixHt;
            $totalHt = $totalHtAvantRemise * (1 - $remisePct / 100.0);
            $totalTva = $totalHt * ($tvaPct / 100.0);
            $totalTtc = $totalHt + $totalTva;

            $result[] = [
                'id' => $ligne->getId(),
                'reference' => $ligne->getReference(),
                'produit' => $ligne->getProduit(),
                'description' => method_exists($ligne, 'getDescription') ? $ligne->getDescription() : '',
                'unite' => method_exists($ligne, 'getUnite') ? $ligne->getUnite() : '',
                'quantite' => $quantite,
                'prix_ht' => $prixHt,
                'remise' => $remisePct,
                'tva' => $tvaPct,
                'total_ht' => round($totalHt, 2),
                'total_tva' => round($totalTva, 2),
                'total_ttc' => round($totalTtc, 2),
            ];
        }
        return $result;
    }
private function getAllFactureParams(ParamsVentesRepository $paramsVentesRepository): array
{
    $params = $paramsVentesRepository->findBy(['module' => 'facture']);
    $result = [];

    foreach ($params as $param) {
        $etat = $param->getEtat() ?: $param->getValeur();
        $result[$param->getCle()] = in_array(
            strtolower(trim((string)$etat)),
            ['on', '1', 'true', 'enabled', 'oui', 'yes'],
            true
        );
    }

    return $result;
}
/**
 * Génère un PDF de la facture et le sauvegarde dans /public/factures/
 * Retourne le chemin complet du fichier généré.
 */
private function savePdfLocally(Facture $facture, ParamsVentesRepository $paramsVentesRepository): string
{
    $pdfOptions = new \Dompdf\Options();
    $pdfOptions->set('defaultFont', 'Arial');
    $pdfOptions->set('isRemoteEnabled', true);

    $dompdf = new \Dompdf\Dompdf($pdfOptions);

    $html = $this->renderView('facture/pdf.html.twig', [
        'facture' => $facture,
        'factureParams' => $this->getAllFactureParams($paramsVentesRepository),
        'showRetenueSource' => $this->shouldDisplayRetenueSource($paramsVentesRepository),
        'showRetenueGarantie' => $this->shouldDisplayRetenueGarantie($paramsVentesRepository),
    ]);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Crée le dossier s’il n’existe pas
    $outputDir = $this->getParameter('kernel.project_dir') . '/public/factures';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0775, true);
    }

    $filePath = $outputDir . '/facture_' . $facture->getId() . '.pdf';
    file_put_contents($filePath, $dompdf->output());

    return $filePath;
}

}