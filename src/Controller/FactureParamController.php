<?php
// src/Controller/FactureParamController.php
namespace App\Controller;

use App\Repository\FactureParamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class FactureParamController extends AbstractController
{
    #[Route('/facture-param/toggle/{factureId}/{cleId}', name: 'facture_param_toggle')]
    public function toggle(FactureParamRepository $repo, int $factureId, int $cleId): JsonResponse
    {
        // Bascule la valeur ON/OFF
        $repo->toggleParam($factureId, $cleId);

        // Récupère la nouvelle valeur pour renvoyer à l'AJAX
        $param = $repo->findOneBy([
            'factureId' => $factureId,
            'cleId' => $cleId
        ]);

        return new JsonResponse([
            'status' => 'success',
            'valeur' => $param ? $param->getValeur() : null
        ]);
    }
}
