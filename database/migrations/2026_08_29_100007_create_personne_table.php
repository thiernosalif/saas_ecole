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
        Schema::create('personne', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->string('type', 20);
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance', 100)->nullable();
            $table->char('genre', 1)->nullable();
            $table->string('matricule', 50)->nullable();
            $table->text('photo_url')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('adresse')->nullable();
            $table->string('nationalite', 50)->nullable();
            $table->string('num_acte_naissance', 50)->nullable();
            $table->string('groupe_sanguin', 5)->nullable();
            $table->text('allergies')->nullable();
            $table->string('keycloak_id', 100)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->string('created_by', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personne');
    }
};
