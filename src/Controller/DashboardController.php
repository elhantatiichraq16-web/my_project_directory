<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\FactureRepository;
use App\Entity\Facture;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function dashboard(FactureRepository $factureRepo): Response
    {
        // Récupérer toutes les factures (non supprimées)
        $factures = $factureRepo->findAll();
        
        // Si votre entité a un champ 'deleted', filtrez-les
        $factures = array_filter($factures, function(Facture $f) {
            if (method_exists($f, 'isDeleted')) {
                return !$f->isDeleted();
            }
            return true;
        });

        $totalInvoices = count($factures);

        // ✅ Calculer les totaux en recalculant depuis les lignes
        $totalHT = 0;
        $totalTTC = 0;
        $totalPaid = 0;
        
        foreach ($factures as $facture) {
            $ht = 0;
            $tva = 0;
            
            // Calculer HT et TVA depuis les lignes
            foreach ($facture->getLignes() as $ligne) {
                $quantite = (float) $ligne->getQuantite();
                $prixHt = (float) $ligne->getPrixHt();
                $remise = (float) ($ligne->getRemise() ?? 0);
                $tauxTva = (float) ($ligne->getTva() ?? 0);
                
                $htLigne = $quantite * $prixHt;
                $htApresRemise = $htLigne * (1 - $remise / 100);
                
                $ht += $htApresRemise;
                $tva += $htApresRemise * ($tauxTva / 100);
            }
            
            // Appliquer la remise globale si elle existe
            $remiseGlobale = (float) ($facture->getRemiseGlobale() ?? 0);
            if ($remiseGlobale > 0) {
                $montantRemiseGlobale = $ht * ($remiseGlobale / 100);
                $ht -= $montantRemiseGlobale;
                $tva = $ht * ($tva / ($ht + $montantRemiseGlobale)); // Recalcul proportionnel TVA
            }
            
            $ttc = $ht + $tva;
            
            // Soustraire les retenues
            if ($facture->getRetenueSource()) {
                $ttc -= (float) $facture->getRetenueSource();
            }
            if ($facture->getRetenueGarantie()) {
                $ttc -= (float) $facture->getRetenueGarantie();
            }
            
            $totalHT += $ht;
            $totalTTC += $ttc;
            
            // ✅ Utiliser getEtat() au lieu de getStatut()
            if ($facture->getEtat() === 'Payée') {
                $totalPaid += $ttc;
            }
        }

        // ✅ Filtrer par état (pas statut)
        $paidInvoices = array_filter($factures, fn(Facture $f) => $f->getEtat() === 'Payée');
        $pendingInvoices = array_filter(
            $factures,
            fn(Facture $f) => in_array($f->getEtat(), ['Brouillon', 'Validée', 'Envoyée'], true)
        );

        // Compter les clients actifs
        $tiers = [];
        foreach ($factures as $facture) {
            if ($facture->getTier()) {
                $tiers[$facture->getTier()->getId()] = $facture->getTier();
            }
        }
        $activeClients = count($tiers);

        // Trier les factures par date
        $sortedInvoices = $factures;
        usort($sortedInvoices, fn(Facture $a, Facture $b) => 
            ($b->getDateCreation()?->getTimestamp() ?? 0) <=> ($a->getDateCreation()?->getTimestamp() ?? 0)
        );

        // Calculer montants en attente et en retard
        $pendingAmount = 0;
        $overdueAmount = 0;
        
        foreach ($pendingInvoices as $facture) {
            $ttc = $this->calculateFactureTTC($facture);
            $pendingAmount += $ttc;
        }
        
        $overdueInvoices = array_filter($factures, function(Facture $f) {
            // Une facture est en retard si elle a une date d'échéance passée et n'est pas payée
            if ($f->getEtat() === 'Payée') {
                return false;
            }
            $dateEcheance = $f->getDateEcheance();
            if (!$dateEcheance) {
                return false;
            }
            return $dateEcheance < new \DateTime();
        });
        
        foreach ($overdueInvoices as $facture) {
            $overdueAmount += $this->calculateFactureTTC($facture);
        }

        // Top clients
        $topClients = [];
        foreach ($factures as $facture) {
            $client = $facture->getTier();
            if (!$client) {
                continue;
            }

            $clientId = $client->getId();
            if (!isset($topClients[$clientId])) {
                $topClients[$clientId] = [
                    'name' => $client->getNom() ?? 'Client #' . $clientId,
                    'amount' => 0,
                ];
            }

            $topClients[$clientId]['amount'] += $this->calculateFactureTTC($facture);
        }
        usort($topClients, fn(array $a, array $b) => $b['amount'] <=> $a['amount']);
        $topClients = array_slice($topClients, 0, 5);

        // Données mensuelles
        $monthLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        $monthlyTotals = array_fill(1, 12, 0.0);
        $monthlyPaidTotals = array_fill(1, 12, 0.0);

        foreach ($factures as $facture) {
            $creationDate = $facture->getDateCreation();
            if (!$creationDate) {
                continue;
            }

            $monthIndex = (int) $creationDate->format('n');
            $ttc = $this->calculateFactureTTC($facture);
            $monthlyTotals[$monthIndex] += $ttc;

            if ($facture->getEtat() === 'Payée') {
                $monthlyPaidTotals[$monthIndex] += $ttc;
            }
        }

        $monthlyRevenueData = [
            'labels' => $monthLabels,
            'invoiced' => array_map(
                fn(int $month) => round($monthlyTotals[$month], 2),
                range(1, 12)
            ),
            'paid' => array_map(
                fn(int $month) => round($monthlyPaidTotals[$month], 2),
                range(1, 12)
            ),
        ];

        $stats = [
            'totalInvoices' => $totalInvoices,
            'totalHT' => $totalHT,
            'totalTTC' => $totalTTC,
            'totalPaid' => $totalPaid,
            'paidInvoices' => count($paidInvoices),
            'pendingInvoices' => count($pendingInvoices),
            'activeClients' => $activeClients,
            'recentInvoices' => array_slice($sortedInvoices, 0, 5),
            'pendingAmount' => $pendingAmount,
            'overdueAmount' => $overdueAmount,
            'topClients' => $topClients,
            'monthlyRevenueData' => $monthlyRevenueData,
        ];

        return $this->render('dashboard.html.twig', $stats);
    }

    /**
     * Calcule le total TTC d'une facture à partir de ses lignes
     */
    private function calculateFactureTTC(Facture $facture): float
    {
        $ht = 0;
        $tva = 0;
        
        foreach ($facture->getLignes() as $ligne) {
            $quantite = (float) $ligne->getQuantite();
            $prixHt = (float) $ligne->getPrixHt();
            $remise = (float) ($ligne->getRemise() ?? 0);
            $tauxTva = (float) ($ligne->getTva() ?? 0);
            
            $htLigne = $quantite * $prixHt;
            $htApresRemise = $htLigne * (1 - $remise / 100);
            
            $ht += $htApresRemise;
            $tva += $htApresRemise * ($tauxTva / 100);
        }
        
        // Appliquer la remise globale
        $remiseGlobale = (float) ($facture->getRemiseGlobale() ?? 0);
        if ($remiseGlobale > 0 && $ht > 0) {
            $montantRemiseGlobale = $ht * ($remiseGlobale / 100);
            $ht -= $montantRemiseGlobale;
            $tva = $ht * ($tva / ($ht + $montantRemiseGlobale));
        }
        
        $ttc = $ht + $tva;
        
        // Soustraire les retenues
        if ($facture->getRetenueSource()) {
            $ttc -= (float) $facture->getRetenueSource();
        }
        if ($facture->getRetenueGarantie()) {
            $ttc -= (float) $facture->getRetenueGarantie();
        }
        
        return $ttc;
    }
}