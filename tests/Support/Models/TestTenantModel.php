<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TestTenantModel extends Model
{
    use BelongsToTenant;

    protected $table = 'test_tenant_models';

    protected $fillable = ['tenant_id', 'label'];

    public $timestamps = false;
}
