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
        Schema::table('personne', function (Blueprint $table) {
            $table->index(['tenant_id', 'type'], 'idx_personne_tenant_type');
            $table->index(['tenant_id', 'matricule'], 'idx_personne_tenant_matricule');
        });

        Schema::table('inscription', function (Blueprint $table) {
            $table->index(['tenant_id', 'classe_id', 'annee_id'], 'idx_inscription_tenant');
        });

        Schema::table('absence', function (Blueprint $table) {
            $table->index(['tenant_id', 'eleve_id', 'date'], 'idx_absence_tenant_eleve');
        });

        Schema::table('lien_parente', function (Blueprint $table) {
            $table->index(['tenant_id', 'eleve_id'], 'idx_lien_parente_tenant');
        });

        Schema::table('note', function (Blueprint $table) {
            $table->index(['tenant_id', 'eleve_id', 'trimestre_id'], 'idx_note_tenant_eleve');
            $table->index(['tenant_id', 'matiere_id', 'trimestre_id'], 'idx_note_tenant_matiere');
        });

        Schema::table('bulletin', function (Blueprint $table) {
            $table->index(['tenant_id', 'eleve_id', 'trimestre_id'], 'idx_bulletin_tenant');
        });

        Schema::table('seance', function (Blueprint $table) {
            $table->index(['tenant_id', 'classe_id', 'jour_semaine'], 'idx_seance_tenant_classe');
        });

        Schema::table('examen', function (Blueprint $table) {
            $table->index(['tenant_id', 'classe_id', 'date_examen'], 'idx_examen_tenant');
        });

        Schema::table('facture', function (Blueprint $table) {
            $table->index(['tenant_id', 'eleve_id', 'statut'], 'idx_facture_tenant_eleve');
            $table->index(['tenant_id', 'statut', 'due_at'], 'idx_facture_tenant_statut');
        });

        Schema::table('reglement', function (Blueprint $table) {
            $table->index(['tenant_id', 'facture_id'], 'idx_reglement_tenant');
        });

        // DESC explicite (§5.3) : non exprimable via Blueprint::index(), SQL brut requis.
        DB::statement('CREATE INDEX idx_audit_tenant_date ON audit_log (tenant_id, created_at DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_audit_tenant_date');

        Schema::table('reglement', fn (Blueprint $table) => $table->dropIndex('idx_reglement_tenant'));

        Schema::table('facture', function (Blueprint $table) {
            $table->dropIndex('idx_facture_tenant_eleve');
            $table->dropIndex('idx_facture_tenant_statut');
        });

        Schema::table('examen', fn (Blueprint $table) => $table->dropIndex('idx_examen_tenant'));
        Schema::table('seance', fn (Blueprint $table) => $table->dropIndex('idx_seance_tenant_classe'));
        Schema::table('bulletin', fn (Blueprint $table) => $table->dropIndex('idx_bulletin_tenant'));

        Schema::table('note', function (Blueprint $table) {
            $table->dropIndex('idx_note_tenant_eleve');
            $table->dropIndex('idx_note_tenant_matiere');
        });

        Schema::table('lien_parente', fn (Blueprint $table) => $table->dropIndex('idx_lien_parente_tenant'));
        Schema::table('absence', fn (Blueprint $table) => $table->dropIndex('idx_absence_tenant_eleve'));
        Schema::table('inscription', fn (Blueprint $table) => $table->dropIndex('idx_inscription_tenant'));

        Schema::table('personne', function (Blueprint $table) {
            $table->dropIndex('idx_personne_tenant_type');
            $table->dropIndex('idx_personne_tenant_matricule');
        });
    }
};
