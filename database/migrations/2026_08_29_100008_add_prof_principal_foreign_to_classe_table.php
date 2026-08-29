<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * classe.prof_principal_id référence personne(id) (§5.2, en commentaire dans le DDL),
 * mais personne est créée après classe dans l'ordre du §7 — la FK ne peut être posée
 * qu'ici, une fois la table cible existante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classe', function (Blueprint $table) {
            $table->foreign('prof_principal_id')->references('id')->on('personne');
        });
    }

    public function down(): void
    {
        Schema::table('classe', function (Blueprint $table) {
            $table->dropForeign(['prof_principal_id']);
        });
    }
};
