<?php

namespace App\Controller;

use App\Entity\Tiers;
use App\Form\TiersType;
use App\Repository\TiersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TiersController extends AbstractController
{
    // Liste de tous les clients
    #[Route('/clients', name: 'app_clients_list')]
    public function index(TiersRepository $tiersRepository): Response
    {
        $clients = $tiersRepository->findAll();

        return $this->render('tiers/index.html.twig', [
            'clients' => $clients,
            'singleTier' => false,
        ]);
    }

    // Création d'un nouveau client
    #[Route('/clients/new', name: 'app_clients_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tier = new Tiers();
        $form = $this->createForm(TiersType::class, $tier);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->persist($tier);
                $entityManager->flush();

                $this->addFlash('success', 'Client ajouté avec succès.');
                return $this->redirectToRoute('app_clients_list');
            } else {
                $this->addFlash('error', 'Le formulaire contient des erreurs, veuillez vérifier les champs.');
            }
        }

        return $this->render('tiers/new.html.twig', [
            'form' => $form,
        ]);
    }

    // Affichage d’un client spécifique
    #[Route('/tiers/{id}', name: 'app_tiers_show', requirements: ['id' => '\d+'])]
    public function show(Tiers $tier): Response
    {
        return $this->render('tiers/index.html.twig', [
            'clients' => [$tier],
            'singleTier' => true,
        ]);
    }

    // ✅ Édition d’un client existant
    #[Route('/clients/{id}/edit', name: 'app_clients_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tiers $client, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TiersType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Client modifié avec succès.');

            return $this->redirectToRoute('app_clients_list');
        }

        return $this->render('tiers/edit.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
        ]);
    }

    // ✅ Suppression d’un client (avec CSRF)
    #[Route('/clients/{id}', name: 'app_clients_delete', methods: ['POST'])]
    public function delete(Request $request, Tiers $client, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $client->getId(), $request->request->get('_token'))) {
            $entityManager->remove($client);
            $entityManager->flush();
            $this->addFlash('success', 'Client supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_clients_list');
    }
}
