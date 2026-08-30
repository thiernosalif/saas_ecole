<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Models\PlanTarifaire;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Jeu de données pour vérifier manuellement le portail Super Admin
 * (Session 8) : les 4 plans tarifaires (§15.3), le rôle SUPER_ADMIN scopé
 * sur le "team" plateforme, et un compte de démo. Mot de passe : "password".
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['nom' => 'Starter', 'prix_mensuel' => 15000, 'max_eleves' => 300, 'modules_inclus' => ['scolarite', 'notes', 'absences']],
            ['nom' => 'Standard', 'prix_mensuel' => 35000, 'max_eleves' => 1000, 'modules_inclus' => ['scolarite', 'notes', 'absences', 'planning', 'comptabilite']],
            ['nom' => 'Premium', 'prix_mensuel' => 75000, 'max_eleves' => 5000, 'modules_inclus' => ['scolarite', 'notes', 'absences', 'planning', 'comptabilite', 'documents', 'notifications']],
            ['nom' => 'Reseau', 'prix_mensuel' => 150000, 'max_eleves' => null, 'modules_inclus' => ['all']],
        ])->each(fn (array $plan) => PlanTarifaire::firstOrCreate(['nom' => $plan['nom']], $plan));

        app(PermissionRegistrar::class)->setPermissionsTeamId(Etablissement::PLATFORM_TEAM_ID);
        Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'web', 'team_id' => Etablissement::PLATFORM_TEAM_ID]);

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@plateforme.sn'],
            ['name' => 'Super Admin', 'password' => 'password', 'tenant_id' => null],
        );
        $superAdmin->assignRole('SUPER_ADMIN');
    }
}
