<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Catalogues;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UtilisateurController extends AbstractController
{
    #[Route('/utilisateur', name: 'app_utilisateur_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $utilisateurs = $em->getRepository(Utilisateur::class)->findAll();
        $produits = $em->getRepository(Catalogues::class)->findAll();

        return $this->render('utilisateur/list.html.twig', [
            'utilisateurs' => $utilisateurs,
            'produits' => $produits,
            'successMessage' => null,
            'errorMessage' => null
        ]);
    }

    #[Route('/utilisateur/create', name: 'app_utilisateur_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('create_user', $request->request->get('_token'))) {
            $this->addFlash('error', 'Le formulaire a expiré, veuillez réessayer.');
            return $this->redirectToRoute('app_utilisateur_list');
        }

        $requiredFields = ['name', 'prenom', 'email', 'adresse', 'telephone', 'password'];
        foreach ($requiredFields as $field) {
            if (!trim((string) $request->request->get($field))) {
                $this->addFlash('error', 'Merci de remplir tous les champs obligatoires.');
                return $this->redirectToRoute('app_utilisateur_list');
            }
        }

        $utilisateur = new Utilisateur();
        $utilisateur->setName($request->request->get('name'));
        $utilisateur->setPrenom($request->request->get('prenom'));
        $utilisateur->setEmail($request->request->get('email'));
        $utilisateur->setAdresse($request->request->get('adresse'));
        $utilisateur->setTelephone($request->request->get('telephone'));
        $utilisateur->setPassword(password_hash($request->request->get('password'), PASSWORD_BCRYPT));

        if ($request->request->has('photo') && !empty($request->request->get('photo'))) {
            $utilisateur->setPhoto($request->request->get('photo'));
        }

        $em->persist($utilisateur);
        $em->flush();

        $this->addFlash('success', 'Client créé avec succès.');

        return $this->redirectToRoute('app_utilisateur_list');
    }

    #[Route('/utilisateur/delete/{id}', name: 'app_utilisateur_delete')]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(Utilisateur::class)->find($id);
        if($user){
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Utilisateur non trouvé.');
        }

        return $this->redirectToRoute('app_utilisateur_list');
    }

    #[Route('/utilisateur/update/{id}', name: 'app_utilisateur_update', methods:['POST'])]
    public function update(int $id, EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $user = $em->getRepository(Utilisateur::class)->find($id);
        if(!$user){
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_utilisateur_list');
        }

        $user->setName($request->request->get('name'));
        $user->setPrenom($request->request->get('prenom'));
        $user->setEmail($request->request->get('email'));
        $user->setAdresse($request->request->get('adresse'));
        $user->setTelephone($request->request->get('telephone'));

        // Upload photo
        $photoFile = $request->files->get('photo');
        if ($photoFile) {
            $newFilename = uniqid().'.'.$photoFile->guessExtension();
            $photoFile->move($this->getParameter('photos_directory'), $newFilename);
            $user->setPhoto($newFilename);
        }

        $em->flush();
        $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
        return $this->redirectToRoute('app_utilisateur_list');
    }
}