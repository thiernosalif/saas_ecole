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
        Schema::create('seance', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('classe_id')->constrained('classe');
            $table->foreignUuid('matiere_id')->constrained('matiere');
            $table->foreignUuid('prof_id')->nullable()->constrained('personne');
            $table->foreignUuid('salle_id')->nullable()->constrained('salle');
            $table->foreignUuid('annee_id')->nullable()->constrained('annee_scolaire');
            $table->integer('jour_semaine');
            $table->time('heure_debut');
            $table->time('heure_fin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seance');
    }
};
