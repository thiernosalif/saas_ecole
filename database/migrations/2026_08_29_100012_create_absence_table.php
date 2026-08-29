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
        Schema::create('absence', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('eleve_id')->constrained('personne');
            // FK vers matiere posée dans add_matiere_foreign_to_absence_table,
            // une fois la table matiere créée (référence en avant dans PROJET.md §5.2).
            $table->uuid('matiere_id')->nullable();
            $table->date('date');
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->string('type', 10);
            $table->boolean('justifiee')->default(false);
            $table->text('justificatif')->nullable();
            $table->foreignUuid('saisie_par')->nullable()->constrained('personne');
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence');
    }
};
