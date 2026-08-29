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
        Schema::create('facture', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('eleve_id')->constrained('personne');
            $table->foreignUuid('annee_id')->nullable()->constrained('annee_scolaire');
            $table->string('numero', 30)->nullable()->unique();
            $table->decimal('montant_total', 12, 2);
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->string('statut', 20)->default('EN_ATTENTE');
            $table->date('due_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture');
    }
};
