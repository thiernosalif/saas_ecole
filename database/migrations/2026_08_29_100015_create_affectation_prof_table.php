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
        Schema::create('affectation_prof', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('prof_id')->constrained('personne');
            $table->foreignUuid('classe_id')->constrained('classe');
            $table->foreignUuid('matiere_id')->constrained('matiere');
            $table->foreignUuid('annee_id')->constrained('annee_scolaire');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectation_prof');
    }
};
