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
        Schema::create('document_genere', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('eleve_id')->nullable()->constrained('personne');
            $table->string('type', 30);
            $table->text('s3_key');
            $table->timestampTz('genere_at')->useCurrent();
            $table->foreignUuid('genere_par')->nullable()->constrained('personne');
            $table->foreignUuid('annee_id')->nullable()->constrained('annee_scolaire');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_genere');
    }
};
