<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etablissement', function (Blueprint $table) {
            $table->string('couleur_primaire', 7)->nullable();
            $table->string('couleur_secondaire', 7)->nullable();
            $table->text('logo_s3_key')->nullable();
            $table->string('nom_court', 50)->nullable();
            $table->string('slogan', 200)->nullable();
            $table->date('date_debut_essai')->nullable();
            $table->date('date_fin_essai')->nullable();
            $table->timestampTz('date_suspension')->nullable();
            $table->text('motif_suspension')->nullable();
            $table->timestampTz('date_resiliation')->nullable();
            $table->integer('nb_eleves_max')->default(300);
            $table->integer('stockage_max_go')->default(5);
            $table->jsonb('modules_actifs')->nullable();
            $table->string('contact_directeur', 150)->nullable();
            $table->string('telephone_ecole', 20)->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('pays', 50)->default('Sénégal');
        });
    }

    public function down(): void
    {
        Schema::table('etablissement', function (Blueprint $table) {
            $table->dropColumn([
                'couleur_primaire',
                'couleur_secondaire',
                'logo_s3_key',
                'nom_court',
                'slogan',
                'date_debut_essai',
                'date_fin_essai',
                'date_suspension',
                'motif_suspension',
                'date_resiliation',
                'nb_eleves_max',
                'stockage_max_go',
                'modules_actifs',
                'contact_directeur',
                'telephone_ecole',
                'ville',
                'pays',
            ]);
        });
    }
};
