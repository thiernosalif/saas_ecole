<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pas dans §7 (PROJET_LARAVEL.md liste facture/reglement mais pas
        // ecriture_comptable) : §4.2 demande explicitement le modèle, donc on
        // ajoute la table manquante — une écriture par règlement confirmé
        // (espèces ou en ligne), en journal simple (pas de débit/crédit à
        // double entrée, hors scope MVP).
        Schema::create('ecriture_comptable', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('reglement_id')->constrained('reglement');
            $table->foreignUuid('facture_id')->constrained('facture');
            $table->decimal('montant', 12, 2);
            $table->string('moyen_paiement', 30);
            $table->string('libelle', 200);
            $table->timestampTz('date_mouvement');
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecriture_comptable');
    }
};
