<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ResolveTenant appelle setPermissionsTeamId(etablissement.id), qui est un UUID
 * (§5.1) : la migration spatie par défaut typait team_id en bigint, incompatible
 * avec un UUID sous Postgres ("invalid input syntax for type bigint"). On aligne
 * le type de team_id sur celui de etablissement.id plutôt que d'introduire un
 * second identifiant bigint juste pour spatie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_team_id_name_guard_name_unique');
            $table->dropIndex('roles_team_foreign_key_index');
            $table->dropColumn('team_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('team_id')->nullable()->after('id');
            $table->index('team_id', 'roles_team_foreign_key_index');
            $table->unique(['team_id', 'name', 'guard_name']);
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->dropIndex('model_has_roles_team_foreign_key_index');
            $table->dropColumn('team_id');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->uuid('team_id');
            $table->index('team_id', 'model_has_roles_team_foreign_key_index');
            $table->primary(['team_id', 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->dropIndex('model_has_permissions_team_foreign_key_index');
            $table->dropColumn('team_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->uuid('team_id');
            $table->index('team_id', 'model_has_permissions_team_foreign_key_index');
            $table->primary(['team_id', 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'name', 'guard_name']);
            $table->dropIndex('roles_team_foreign_key_index');
            $table->dropColumn('team_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable()->after('id');
            $table->index('team_id', 'roles_team_foreign_key_index');
            $table->unique(['team_id', 'name', 'guard_name']);
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->dropIndex('model_has_roles_team_foreign_key_index');
            $table->dropColumn('team_id');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id');
            $table->index('team_id', 'model_has_roles_team_foreign_key_index');
            $table->primary(['team_id', 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->dropIndex('model_has_permissions_team_foreign_key_index');
            $table->dropColumn('team_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id');
            $table->index('team_id', 'model_has_permissions_team_foreign_key_index');
            $table->primary(['team_id', 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });
    }
};
