<?php

namespace App\Controller;

use App\Repository\ParamsVentesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParametrageController extends AbstractController
{
    // ==========================================================
    // 🧭 PAGE PRINCIPALE DU PARAMÉTRAGE
    // ==========================================================
    #[Route('/parametrage', name: 'app_parametrage_index', methods: ['GET', 'POST'])]
    public function index(Request $request, ParamsVentesRepository $paramsVentesRepository, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('submit', (string) $request->headers->get('X-CSRF-TOKEN'))) {

                $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez réessayer.');
                return $this->redirectToRoute('app_parametrage_index');
            }

            $submittedValues = $request->request->all('params') ?? [];
            $submittedTitles = $request->request->all('paramsTitre') ?? [];
            $hasChanges = false;

            foreach ($submittedValues as $id => $rawValue) {
                if (!is_numeric($id)) continue;

                $param = $paramsVentesRepository->find((int) $id);
                if (!$param) continue;

                $type = strtolower((string) $param->getType());
                $isBoolean = in_array($type, ['switch', 'bool', 'boolean', 'checkbox'], true);

                // 🔹 1. Titre
                if (isset($submittedTitles[$id])) {
                    $newTitle = trim((string) $submittedTitles[$id]);
                    if ($newTitle !== $param->getTitre()) {
                        $param->setTitre($newTitle);
                        $param->setUpdatedAt(new \DateTimeImmutable());
                        $entityManager->persist($param);
                        $hasChanges = true;
                    }
                }

                // 🔹 2. Booléen ON/OFF
                if ($isBoolean) {
                    $newEtat = $this->normalizeParamValue('switch', $rawValue);

                    if ($param->getEtat() !== $newEtat) {
                        $param->setEtat($newEtat);
                        $param->setValeur($newEtat === 'on' ? 1.0 : 0.0);
                        $param->setUpdatedAt(new \DateTimeImmutable());
                        $entityManager->persist($param);
                        $hasChanges = true;
                    }

                    continue;
                }

                // 🔹 3. Autres types
                $normalizedValue = $this->normalizeParamValue($type, $rawValue);
                $currentValue = $param->getValeur();

                if ($type === 'number' || is_numeric($normalizedValue)) {
                    $normalizedValue = (float) $normalizedValue;
                }

                if ($currentValue !== $normalizedValue) {
                    $param->setValeur($normalizedValue);
                    $param->setEtat('on');
                    $param->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->persist($param);
                    $hasChanges = true;
                }
            }

            if ($hasChanges) {
                $entityManager->flush();
                $this->addFlash('success', '✅ Paramètres enregistrés avec succès.');
            } else {
                $this->addFlash('info', 'Aucun changement détecté.');
            }

            return $this->redirectToRoute('app_parametrage_index');
        }

        // 🔹 Affichage
        $allParams = $paramsVentesRepository->findBy([], [
            'module' => 'ASC',
            'categorie' => 'ASC',
            'cle' => 'ASC',
        ]);

        $modules = $this->buildModulesStructure($allParams);

        return $this->render('parametrage/index.html.twig', [
            'modules' => $modules,
        ]);
    }

    // ==========================================================
    // ⚙️ FRAGMENT POUR RECHARGEMENT AJAX PARTIEL
    // ==========================================================
    #[Route('/parametrage/fragment', name: 'app_parametrage_fragment', methods: ['GET'])]
    public function fragment(Request $request, ParamsVentesRepository $paramsVentesRepository): Response
    {
        $allParams = $paramsVentesRepository->findBy([], [
            'module' => 'ASC',
            'categorie' => 'ASC',
            'cle' => 'ASC',
        ]);

        $modules = $this->buildModulesStructure($allParams);

        if (!$request->isXmlHttpRequest()) {
            return $this->render('parametrage/index.html.twig', [
                'modules' => $modules,
            ]);
        }

        return $this->render('parametrage/_content.html.twig', [
            'modules' => $modules,
            'is_modal' => true,
        ]);
    }

    // ==========================================================
    // 🔘 AJAX : TOGGLE ON/OFF D'UN PARAMÈTRE
    // ==========================================================
    #[Route('/parametrage/toggle/{id}', name: 'app_parametrage_toggle', methods: ['POST'])]
    public function toggleParam(
        int $id,
        Request $request,
        ParamsVentesRepository $paramsVentesRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $param = $paramsVentesRepository->find($id);

        if (!$param) {
            return $this->json(['success' => false, 'message' => 'Paramètre introuvable.'], 404);
        }

        $type = strtolower((string) $param->getType());
        if (!in_array($type, ['switch', 'bool', 'boolean', 'checkbox'], true)) {
            return $this->json(['success' => false, 'message' => 'Ce paramètre ne peut pas être basculé.'], 400);
        }

        // ✅ Vérification du token CSRF
       if (!$this->isCsrfTokenValid('submit', (string) $request->headers->get('X-CSRF-TOKEN'))) {

            return $this->json(['success' => false, 'message' => 'Jeton CSRF invalide.'], 419);
        }

        // ✅ Lecture et validation du JSON
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['success' => false, 'message' => 'JSON invalide.'], 400);
        }

        if (!array_key_exists('value', $data)) {
            return $this->json(['success' => false, 'message' => 'Valeur manquante.'], 400);
        }

        $newValue = $data['value'];

        // ✅ Normalisation de la valeur
        $normalizedValue = in_array(strtolower((string) $newValue), ['on', '1', 'true', 'enabled', 'oui', 'yes'], true)
            ? 'on'
            : 'off';

        // ✅ Mise à jour du paramètre
        $param->setEtat($normalizedValue);
        $param->setValeur($normalizedValue === 'on' ? 1.0 : 0.0);
        $param->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($param);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'etat' => $param->getEtat(),
            'id' => $param->getId(),
            'cle' => $param->getCle(),
            'message' => sprintf("Paramètre '%s' mis à jour : %s", $param->getCle(), $param->getEtat()),
        ]);
    }

    // ==========================================================
    // 🧩 OUTILS INTERNES
    // ==========================================================
    private function normalizeParamValue(string $type, mixed $value): string|float
    {
        $loweredValue = strtolower((string) $value);

        if (in_array($type, ['switch', 'bool', 'boolean', 'checkbox'], true)) {
            return in_array($loweredValue, ['on', '1', 'true', 'enabled', 'oui', 'yes'], true)
                ? 'on'
                : 'off';
        }

        if ($type === 'number') {
            return (float) $value;
        }

        return (string) $value;
    }

    private function buildModulesStructure(array $params): array
    {
        $modules = [];

        foreach ($params as $param) {
            $moduleName = $param->getModule() ?: 'Autres';
            $moduleSlug = $this->slugify($moduleName);

            if (!isset($modules[$moduleSlug])) {
                $modules[$moduleSlug] = [
                    'slug' => $moduleSlug,
                    'label' => $this->humanizeLabel($moduleName),
                    'categories' => [],
                ];
            }

            $categoryName = $param->getCategorie() ?: 'Options';

            if (!isset($modules[$moduleSlug]['categories'][$categoryName])) {
                $modules[$moduleSlug]['categories'][$categoryName] = [
                    'label' => $this->humanizeLabel($categoryName),
                    'params' => [],
                ];
            }

            $modules[$moduleSlug]['categories'][$categoryName]['params'][] = $param;
        }

        ksort($modules, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($modules as &$module) {
            ksort($module['categories'], SORT_NATURAL | SORT_FLAG_CASE);
            $module['categories'] = array_values($module['categories']);
        }

        return array_values($modules);
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
        return trim($value, '-') ?: 'module';
    }

    private function humanizeLabel(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'Sans titre';
        }

        $value = str_replace(['_', '-'], ' ', $value);
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}
