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
        Schema::create('reglement_saas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('etablissement_id')->constrained('etablissement');
            $table->string('mois', 7); // "2026-08"
            $table->decimal('montant', 12, 2);
            $table->string('moyen_paiement', 30)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('statut', 20)->default('EN_ATTENTE');
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglement_saas');
    }
};
