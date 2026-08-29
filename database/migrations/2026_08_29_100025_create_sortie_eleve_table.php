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
        Schema::create('sortie_eleve', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('eleve_id')->constrained('personne');
            $table->string('type_sortie', 20);
            $table->text('motif')->nullable();
            $table->date('date_sortie');
            $table->string('ecole_destination', 200)->nullable();
            $table->jsonb('documents_remis')->nullable();
            $table->boolean('frais_soldes')->default(false);
            $table->foreignUuid('traite_par')->nullable()->constrained('personne');
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sortie_eleve');
    }
};
