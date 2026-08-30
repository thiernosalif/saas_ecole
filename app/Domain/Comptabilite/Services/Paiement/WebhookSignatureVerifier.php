<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Services\Paiement;

class WebhookSignatureVerifier
{
    /**
     * HMAC-SHA256 générique sur le corps brut de la requête (jamais un
     * payload ré-encodé, qui pourrait différer octet pour octet de ce que
     * le provider a réellement signé), comparé en temps constant. Partagée
     * par Wave et Orange Money plutôt que dupliquée par provider.
     */
    public function verifie(string $secret, string $corpsBrut, ?string $signatureRecue): bool
    {
        if ($secret === '' || ! $signatureRecue) {
            return false;
        }

        $attendue = hash_hmac('sha256', $corpsBrut, $secret);

        return hash_equals($attendue, $signatureRecue);
    }
}
