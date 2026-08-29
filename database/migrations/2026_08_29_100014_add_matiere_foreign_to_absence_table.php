<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * absence.matiere_id REFERENCES matiere(id) (§5.2), mais matiere est créée après
 * absence dans l'ordre du §7 — la FK ne peut être posée qu'ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absence', function (Blueprint $table) {
            $table->foreign('matiere_id')->references('id')->on('matiere');
        });
    }

    public function down(): void
    {
        Schema::table('absence', function (Blueprint $table) {
            $table->dropForeign(['matiere_id']);
        });
    }
};
