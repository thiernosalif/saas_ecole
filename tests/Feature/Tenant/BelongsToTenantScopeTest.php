<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Models\TestTenantModel;

beforeEach(function () {
    Schema::create('test_tenant_models', function (Blueprint $table) {
        $table->id();
        $table->string('tenant_id');
        $table->string('label');
    });
});

afterEach(function () {
    Schema::dropIfExists('test_tenant_models');
    app()->forgetInstance('currentTenant');
    app()->forgetInstance('currentTenantId');
});

it('filters records by the current tenant', function () {
    TestTenantModel::withoutTenant()->create(['tenant_id' => 'ecole-a', 'label' => 'A1']);
    TestTenantModel::withoutTenant()->create(['tenant_id' => 'ecole-b', 'label' => 'B1']);

    app()->instance('currentTenantId', 'ecole-a');

    expect(TestTenantModel::all())->toHaveCount(1)
        ->and(TestTenantModel::first()->label)->toBe('A1');
});

it('auto-fills tenant_id from the currentTenantId on create', function () {
    app()->instance('currentTenantId', 'ecole-c');

    $model = TestTenantModel::create(['label' => 'C1']);

    expect($model->tenant_id)->toBe('ecole-c');
});

it('bypasses the tenant scope explicitly via withoutTenant', function () {
    TestTenantModel::withoutTenant()->create(['tenant_id' => 'ecole-a', 'label' => 'A1']);
    TestTenantModel::withoutTenant()->create(['tenant_id' => 'ecole-b', 'label' => 'B1']);

    app()->instance('currentTenantId', 'ecole-a');

    expect(TestTenantModel::withoutTenant()->count())->toBe(2);
});
