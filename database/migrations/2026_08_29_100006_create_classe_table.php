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
        Schema::create('classe', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('niveau_id')->nullable()->constrained('niveau');
            $table->foreignUuid('annee_id')->nullable()->constrained('annee_scolaire');
            $table->string('libelle', 20);
            $table->integer('capacite_max')->default(45);
            // FK vers personne posée dans add_prof_principal_foreign_to_classe_table,
            // une fois la table personne créée (référence en avant dans PROJET.md §5.2).
            $table->uuid('prof_principal_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classe');
    }
};
