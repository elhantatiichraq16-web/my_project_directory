<?php

namespace App\Controller;

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
        // sécurise l'accès à la session (peut renvoyer null si aucune session)
        $this->session = $requestStack->getSession();
    }

    // Redirection vers la liste des factures
    #[Route('/facture', name: 'app_facture')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_factures_list');
    }
// Création d'une facture
#[Route('/facture/create', name: 'app_facture_create')]
public function create(
    Request $request,
    TiersRepository $tiersRepo,
    EntityManagerInterface $entityManager,
    ParamsVentesRepository $paramsVentesRepository,
    ValidationForm $validationForm

): Response {
    $tiers = $tiersRepo->findAll();
    $factureParams = $this->getAllFactureParams($paramsVentesRepository);

    $modePaiement = ['Espèces', 'Chèque', 'Virement bancaire', 'Carte de crédit'];
    $devises = ['MAD', 'EUR', 'USD'];

    // 🟩 Si le formulaire est soumis
    if ($request->isMethod('POST')) {
        try {
            $data = $request->request->all();

// 🧩 Validation via ValidationForm (nouvelle logique)
if (!$validationForm->validate($data)) {
    $errors = $validationForm->getErrors();

    // 🔹 Si c’est une requête AJAX
    if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
            'success' => false,
            'errors' => $errors,
        ], 400);
    }

    // 🔹 Sinon on réaffiche la page avec les erreurs
    return $this->render('facture/create.html.twig', [
        'tiers' => $tiers,
        'modePaiement' => $modePaiement,
        'devises' => $devises,
        'factureParams' => $factureParams,
        'errors' => $errors,
    ]);
}



            // 🧾 Création de la facture si validation OK
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
                $facture->setTier($tier);
            }

            // Retenues
            $facture->setRetenueSource(isset($data['retenue_source']) ? (float)$data['retenue_source'] : null);
            $facture->setRetenueGarantie(isset($data['retenue_garantie']) ? (float)$data['retenue_garantie'] : null);

            $entityManager->persist($facture);
            $entityManager->flush();

 // 🔹 Sauvegarde des lignes produits (création ou mise à jour)
if (!empty($data['produits']) && is_array($data['produits'])) {
    // Supprimer les anciennes lignes liées à la facture
    foreach ($facture->getLignes() as $ancienneLigne) {
        $entityManager->remove($ancienneLigne);
    }
    $entityManager->flush();

    foreach ($data['produits'] as $produit) {
        if (!empty($produit['produit'])) { // ✅ correspond à ton <input name="produit">
            $ligne = new LigneFacture();
            $ligne->setFacture($facture);
            $ligne->setReference($produit['reference'] ?? '');
            $ligne->setProduit($produit['produit']); // ✅ correspond à ton champ
            $ligne->setQuantite((float)($produit['quantite'] ?? 1));
            $ligne->setPrixHt((float)($produit['prix_ht'] ?? 0));
            $ligne->setTva((float)($produit['tva'] ?? 20));
            $ligne->setRemise((float)($produit['remise'] ?? 0));

            // Calcul TTC si envoyé
            if (!empty($produit['total_ttc'])) {
                $ligne->setTotalTtc((float)$produit['total_ttc']);
            } else {
                $ht = $ligne->getQuantite() * $ligne->getPrixHt();
                $remise = $ht * ($ligne->getRemise() / 100);
                $htNet = $ht - $remise;
                $ttc = $htNet + ($htNet * $ligne->getTva() / 100);
                $ligne->setTotalTtc($ttc);
            }

            if (!empty($produit['description']) && method_exists($ligne, 'setDescription')) {
                $ligne->setDescription($produit['description']);
            }

            if (!empty($produit['unite']) && method_exists($ligne, 'setUnite')) {
                $ligne->setUnite($produit['unite']);
            }

            $entityManager->persist($ligne);
        }
    }
    $entityManager->flush();
}


$this->addFlash('success', '✅ Facture enregistrée avec succès !');

// 🔹 En cas de requête AJAX
if ($request->isXmlHttpRequest()) {
    return new JsonResponse(['success' => true, 'factureId' => $facture->getId()]);
}

// 🔹 Redirection vers la liste des factures
return $this->redirectToRoute('app_factures_list');

        } catch (\Throwable $e) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
            }
            throw $e;
        }
    }

    // 🟩 Formulaire initial (GET)
    return $this->render('facture/create.html.twig', [
        'tiers' => $tiers,
        'modePaiement' => $modePaiement,
        'devises' => $devises,
        'factureParams' => $factureParams,
    ]);
}


    // Liste des factures (nom non réservé)
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
 

    // Préparer les lignes avec toutes les informations nécessaires
    $lignes = $this->getLignesData($facture);

    // Calculer les totaux
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

        // Vérif CSRF
        if (!$this->isCsrfTokenValid('edit_facture_' . $facture->getId(), $data['_csrf_token'] ?? '')) {
            $errors[] = 'Le formulaire a expiré, veuillez réessayer.';
        }

        if (empty($errors)) {
            // 🔹 Mise à jour des champs principaux
            $facture->setReference($data['reference']);
            $facture->setObjet($data['objet'] ?? $facture->getObjet());
            $facture->setRetenueSource(isset($data['retenue_source']) ? (float) $data['retenue_source'] : $facture->getRetenueSource());
            $facture->setRetenueGarantie(isset($data['retenue_garantie']) ? (float) $data['retenue_garantie'] : $facture->getRetenueGarantie());
            $facture->setDevise($data['devise'] ?? $facture->getDevise() ?? 'MAD');
            $facture->setEtat($data['etat'] ?? $facture->getEtat() ?? 'Brouillon');
            $facture->setNotes($data['notes'] ?? $facture->getNotes());

            // Remise globale
            if (isset($data['remise_globale'])) {
                $facture->setRemiseGlobale((float) $data['remise_globale']);
            }

            // Dates
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

            // Tier
            $selectedTier = !empty($data['tier']) ? $tiersRepo->find($data['tier']) : null;
            if ($selectedTier) {
                $facture->setTier($selectedTier);
            }

            $facture->setDateModification(new \DateTimeImmutable());

            // 🔹 Gestion des lignes produits
            foreach ($facture->getLignes() as $ligneExistante) {
                $entityManager->remove($ligneExistante);
            }

            if (!empty($data['produits']) && is_array($data['produits'])) {
                foreach ($data['produits'] as $produitData) {
                    // On ignore les lignes vides
                    if (empty($produitData['produit']) && empty($produitData['reference'])) continue;

                    $ligne = new LigneFacture();
                    $ligne->setFacture($facture);
                    $ligne->setReference($produitData['reference'] ?? '');
                    $ligne->setProduit($produitData['produit'] ?? '');
                    $ligne->setQuantite((float)($produitData['quantite'] ?? 1));
                    $ligne->setPrixHT((float)($produitData['prix_ht'] ?? 0));
                    $ligne->setRemise((float)($produitData['remise'] ?? 0));
                    $ligne->setTva((float)($produitData['tva'] ?? 0));
                    $ligne->setTotalTTC((float)($produitData['total_ttc'] ?? 0));

                    if (isset($produitData['description'])) {
                        $ligne->setDescription($produitData['description']);
                    }
                    if (isset($produitData['unite'])) {
                        $ligne->setUnite($produitData['unite']);
                    }

                    $entityManager->persist($ligne);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', '✅ Facture mise à jour avec succès !');
            return $this->redirectToRoute('app_factures_list');
        }
    }

    $devises = ['MAD', 'EUR', 'USD'];

    // 🔹 Calcul des totaux pour affichage
    $totalHt = 0;
    $totalRemise = 0;
    $totalTva = 0;
    $totalTtc = 0;

    foreach ($facture->getLignes() as $ligne) {
        $quantite = (float) $ligne->getQuantite();
        $prixHt = (float) $ligne->getPrixHt();
        $remise = (float) ($ligne->getRemise() ?? 0);
        $tva = (float) ($ligne->getTva() ?? 0);

        $htLigne = $quantite * $prixHt;
        $montantRemise = $htLigne * ($remise / 100);
        $htApresRemise = $htLigne - $montantRemise;
        $montantTva = $htApresRemise * ($tva / 100);
        $ttc = $htApresRemise + $montantTva;

        $totalHt += $htLigne;
        $totalRemise += $montantRemise;
        $totalTva += $montantTva;
        $totalTtc += $ttc;
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

    // 🔹 Récupérer tous les paramètres dynamiques (colonnes, options, etc.)
    $factureParams = $this->getAllFactureParams($paramsVentesRepository);

    // 🔹 Générer le HTML avec les bons paramètres
    $html = $this->renderView('facture/pdf.html.twig', [
        'facture' => $facture,
        'factureParams' => $factureParams, // ✅ on ajoute ça
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

    // Alias /print
    #[Route('/facture/{id}/print', name: 'app_facture_print')]
public function printFacture(Facture $facture, ParamsVentesRepository $paramsVentesRepository): Response
{
    // On réutilise la logique de génération du PDF
    return $this->generatePdf($facture, $paramsVentesRepository);
}


    // Récupération JSON (datatable ou JS)
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
                // Si la colonne deleted n'existe pas, la condition sera ignorée à l'exécution DB
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
                        'name' => $f->getTier()->getNom()
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
            // Pour faciliter le debug côté front (ag-grid), renvoyer JSON d'erreur lisible
            return new JsonResponse(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    // Route utilitaire pour éviter 404 lors du chargement des préférences utilisateurs
    #[Route('/general/table/get-preferences/{table}', name: 'get_table_preferences', methods: ['GET'])]
    public function getTablePreferences(string $table): JsonResponse
    {
        // Pour le moment on renvoie des préférences vides afin d'éviter le blocage du front.
        return new JsonResponse([]);
    }

    // -----------------------
    // PARAMÉTRAGE - affichage
    // -----------------------
    #[Route('/facture/parametrage', name: 'app_facture_parametrage', methods: ['GET'])]
    public function parametrage(ParamsVentesRepository $paramsVentesRepository): Response
    {
        $params = $paramsVentesRepository->findBy(['module' => 'facture']);

        return $this->render('facture/parametrage.html.twig', [
    'params' => $params,
    'factureParams' => $this->getAllFactureParams($paramsVentesRepository),
]);

    }

    // -----------------------
    // PARAMÉTRAGE - toggle générique (AJAX)
    // -----------------------
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

    #[Route('/facture/delete/{id}', name: 'facture_delete', methods: ['DELETE'])]
    public function deleteFacture(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $facture = $em->getRepository(Facture::class)->find($id);

        if (!$facture) {
            return new JsonResponse(['message' => 'Facture non trouvée'], 404);
        }

        // Vérifie si c’est une suppression définitive
        $definitive = $request->query->getBoolean('definitive', false);

        if ($definitive) {
            // Suppression physique
            $em->remove($facture);
            $em->flush();
            return new JsonResponse(['message' => 'Facture supprimée définitivement']);
        }

        // Suppression logique : on met une colonne "deleted" à true si elle existe
        if (method_exists($facture, 'setDeleted')) {
            $facture->setDeleted(true);
        } elseif (method_exists($facture, 'setEtat')) {
            $facture->setEtat('Supprimée');
        }

        $em->flush();

        return new JsonResponse(['message' => 'Facture supprimée avec succès']);
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

}
