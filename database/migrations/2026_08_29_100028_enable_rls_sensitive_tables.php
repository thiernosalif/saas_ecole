<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * §3.4 : filet de sécurité optionnel derrière le Global Scope Eloquent (TenantScope).
 * tenant_id est un VARCHAR(50) — le slug etablissement.tenant_id (§5.2, ResolveTenant) —
 * pas un UUID : la policy compare donc du texte, sans le cast `::uuid` de l'exemple du
 * §3.4 (hérité par erreur de PROJET.md, où le typage diffère).
 * Nécessite `SET app.tenant_id = ?` en début de requête, non fait ici — à brancher sur
 * ResolveTenant ou un listener de connexion dans une session ultérieure.
 */
return new class extends Migration
{
    private array $tables = ['facture', 'reglement', 'note', 'bulletin'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("
                CREATE POLICY tenant_isolation ON {$table}
                USING (tenant_id = current_setting('app.tenant_id', true))
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
