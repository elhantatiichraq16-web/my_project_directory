<?php

namespace App\Controller;

use App\Repository\EmailLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/emails')]
class MailController extends AbstractController
{
    #[Route('/envoyes', name: 'app_mail_list')]
    public function envoyes(EmailLogRepository $emailLogRepository): Response
    {
        $emails = $emailLogRepository->findBy([], ['sentAt' => 'DESC']);

        return $this->render('mail/envoyes.html.twig', [
            'emails' => $emails,
        ]);
    }

    #[Route('/recus', name: 'app_mail_recu')]
    public function recus(): Response
    {
        // 🔹 Exemple de mails reçus (simulation)
        $emailsRecus = [
            [
                'from' => 'client1@example.com',
                'subject' => 'Demande de devis',
                'body' => 'Bonjour, pouvez-vous me faire parvenir un devis ?',
                'receivedAt' => new \DateTime('-2 days'),
            ],
            [
                'from' => 'client2@example.com',
                'subject' => 'Facture non reçue',
                'body' => 'Je n’ai pas encore reçu ma facture.',
                'receivedAt' => new \DateTime('-5 hours'),
            ],
        ];

        return $this->render('mail/recus.html.twig', [
            'emails' => $emailsRecus,
        ]);
    }
}
