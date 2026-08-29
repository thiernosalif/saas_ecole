<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §5.2 : users.tenant_id (NULL = staff plateforme) et users.personne_id sont déjà
 * attendus par le modèle User et le middleware ResolveTenant depuis la Session 2,
 * mais absents de create_users_table (scaffold Laravel par défaut, id bigint).
 * Ajoutés ici, une fois etablissement et personne créées, pour pouvoir poser les FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tenant_id', 50)->nullable()->after('id');
            $table->uuid('personne_id')->nullable()->after('tenant_id');

            $table->foreign('tenant_id')->references('tenant_id')->on('etablissement');
            $table->foreign('personne_id')->references('id')->on('personne');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['personne_id']);
            $table->dropColumn(['tenant_id', 'personne_id']);
        });
    }
};
