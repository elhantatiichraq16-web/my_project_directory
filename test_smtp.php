<?php
/**
 * Test SMTP Configuration
 * Execute: php test_smtp.php
 */

require 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mailerDsn = $_ENV['MAILER_DSN'] ?? null;
$mailerFrom = $_ENV['MAILER_FROM'] ?? null;

echo "═════════════════════════════════════════════════════════════\n";
echo "           🔧 TEST CONFIGURATION SMTP SYMFONY\n";
echo "═════════════════════════════════════════════════════════════\n\n";

if (!$mailerDsn) {
    echo "❌ ERREUR: MAILER_DSN non trouvé dans .env\n";
    exit(1);
}

if (!$mailerFrom) {
    echo "❌ ERREUR: MAILER_FROM non trouvé dans .env\n";
    exit(1);
}

echo "📧 Configuration trouvée:\n";
echo "   MAILER_DSN: " . $mailerDsn . "\n";
echo "   MAILER_FROM: " . $mailerFrom . "\n\n";

try {
    echo "⏳ Tentative de connexion au serveur SMTP...\n";
    
    // Créer le transport
    $transport = Transport::fromDsn($mailerDsn);
    $mailer = new Mailer($transport);
    
    echo "✅ Transport créé avec succès!\n\n";
    
    // Créer un email de test
    $email = (new Email())
        ->from($mailerFrom)
        ->to('test@example.com')
        ->subject('Test SMTP Configuration')
        ->text('Ceci est un email de test pour valider la configuration SMTP.');
    
    echo "📝 Email de test créé:\n";
    echo "   From: " . $mailerFrom . "\n";
    echo "   To: test@example.com\n";
    echo "   Subject: Test SMTP Configuration\n\n";
    
    echo "⏳ Tentative d'envoi...\n";
    $mailer->send($email);
    
    echo "✅ Email envoyé avec succès!\n\n";
    echo "═════════════════════════════════════════════════════════════\n";
    echo "✅ Configuration SMTP VALIDE - Prêt pour la production!\n";
    echo "═════════════════════════════════════════════════════════════\n";
    
} catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
    echo "❌ ERREUR D'ENVOI SMTP:\n";
    echo "   " . $e->getMessage() . "\n\n";
    echo "Vérifiez:\n";
    echo "   1. Les identifiants Gmail (email + mot de passe d'app)\n";
    echo "   2. Que Gmail a activé l'accès sécurisé pour les apps\n";
    echo "   3. La connexion internet\n";
    echo "   4. Le port 587 (TLS)\n\n";
    exit(1);
    
} catch (\Exception $e) {
    echo "❌ ERREUR GÉNÉRALE:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}