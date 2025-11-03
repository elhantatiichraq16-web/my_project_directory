<?php

namespace App\Controller;

use App\Entity\Catalogues;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/catalogue')]
class CatalogueController extends AbstractController
{
    #[Route('/', name: 'catalogue_list')]
    public function index(EntityManagerInterface $em): Response
    {
        $catalogues = $em->getRepository(Catalogues::class)->findAll();

        return $this->render('catalogue/index.html.twig', [
            'catalogues' => $catalogues
        ]);
    }

    #[Route('/new', name: 'catalogue_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');

            if (!$nom) {
                $this->addFlash('error', '❌ Le nom du catalogue est obligatoire.');
                return $this->redirectToRoute('catalogue_new');
            }

            $catalogue = new Catalogues();
            $catalogue->setDesignation($nom);
            $catalogue->setDescription($description ?? '');
            $catalogue->setDateCrea(new \DateTime());

            // ✅ Champs obligatoires avec valeurs par défaut
            $catalogue->setTypecata(1);      // ex: 1 = standard
            $catalogue->setEtatAchat(0);     // ex: 0 = inactif
            $catalogue->setEtatVente(0);     // ex: 0 = inactif
$catalogue->setEtat(1);

            // ✅ Référence unique
            $catalogue->setRefecata('CAT-' . strtoupper(uniqid()));

            $em->persist($catalogue);
            $em->flush();

            $this->addFlash('success', '✅ Catalogue ajouté avec succès !');
            return $this->redirectToRoute('catalogue_list');
        }

        return $this->render('catalogue/new.html.twig');
    }

    #[Route('/{id}/delete', name: 'catalogue_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $catalogue = $em->getRepository(Catalogues::class)->find($id);

        if (!$catalogue) {
            return new JsonResponse(['success' => false, 'message' => 'Catalogue non trouvé.'], 404);
        }

        $em->remove($catalogue);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/show', name: 'catalogue_show', methods: ['GET'])]
    public function show(Catalogues $catalogue): Response
    {
        return $this->render('catalogue/show.html.twig', [
            'catalogue' => $catalogue
        ]);
    }

    #[Route('/{id}/edit', name: 'catalogue_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Catalogues $catalogue, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $catalogue->setDesignation($request->request->get('nom'));
            $catalogue->setDescription($request->request->get('description'));
            $em->flush();

            $this->addFlash('success', '✅ Catalogue mis à jour avec succès !');
            return $this->redirectToRoute('catalogue_list');
        }

        return $this->render('catalogue/edit.html.twig', [
            'catalogue' => $catalogue
        ]);
    }
}
