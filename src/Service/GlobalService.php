<?php
namespace App\Service;

class GlobalService
{
    // Génère une chaîne aléatoire
    public function generateRandomString($type = "letters", $length = 5)
    {
        $characters = $type === "letters" ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    // Décrypte un ID (exemple simple)
    public function decryptId($encryptedId, $abonnement)
    {
        // Ici tu mets ton vrai algorithme de décryptage
        return (int)base64_decode($encryptedId);
    }
}
