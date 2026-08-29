<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->setPermissionsTeamId('ecole-a');

    Route::middleware(['web', 'role:ECOLE_ADMIN'])->get('/__test/ensure-role', fn () => response()->json(['ok' => true]));
});

it('allows access when the user has the required role', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => 'ecole-a']);
    $user->assignRole('ECOLE_ADMIN');

    $this->actingAs($user)
        ->get('/__test/ensure-role')
        ->assertOk();
});

it('denies access with a 403 when the user lacks the required role', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'PROF', 'guard_name' => 'web', 'team_id' => 'ecole-a']);
    $user->assignRole('PROF');

    $this->actingAs($user)
        ->get('/__test/ensure-role')
        ->assertForbidden();
});

it('denies access to a guest with a 403', function () {
    $this->get('/__test/ensure-role')->assertForbidden();
});
