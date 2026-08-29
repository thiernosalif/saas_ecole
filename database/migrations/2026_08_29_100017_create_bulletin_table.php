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
        Schema::create('bulletin', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('eleve_id')->constrained('personne');
            $table->foreignUuid('trimestre_id')->constrained('trimestre');
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->integer('rang')->nullable();
            $table->integer('effectif_classe')->nullable();
            $table->string('mention', 30)->nullable();
            $table->text('appreciation_dir')->nullable();
            $table->integer('nb_absences_justifiees')->default(0);
            $table->integer('nb_absences_non_justifiees')->default(0);
            $table->text('url_pdf')->nullable();
            $table->boolean('valide')->default(false);
            $table->timestampTz('genere_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin');
    }
};
