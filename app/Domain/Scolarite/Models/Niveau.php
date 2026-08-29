<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Niveau extends Model
{
    use HasTenantAudit;

    public const CYCLE_PRIMAIRE = 'PRIMAIRE';

    public const CYCLE_SECONDAIRE = 'SECONDAIRE';

    protected $table = 'niveau';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'libelle',
        'ordre',
        'cycle',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class, 'niveau_id');
    }
}
