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
        Schema::create('note', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('eleve_id')->constrained('personne');
            $table->foreignUuid('matiere_id')->constrained('matiere');
            $table->foreignUuid('trimestre_id')->constrained('trimestre');
            $table->decimal('valeur', 5, 2);
            $table->decimal('coefficient', 4, 1)->default(1.0);
            $table->string('type', 20)->nullable();
            $table->text('appreciation')->nullable();
            $table->foreignUuid('saisie_par')->nullable()->constrained('personne');
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note');
    }
};
