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
        Schema::create('examen', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('classe_id')->constrained('classe');
            $table->foreignUuid('matiere_id')->constrained('matiere');
            $table->foreignUuid('trimestre_id')->nullable()->constrained('trimestre');
            $table->foreignUuid('salle_id')->nullable()->constrained('salle');
            $table->date('date_examen');
            $table->time('heure_debut')->nullable();
            $table->integer('duree_minutes')->nullable();
            $table->decimal('bareme', 5, 2)->default(20.0);
            $table->string('libelle', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen');
    }
};
