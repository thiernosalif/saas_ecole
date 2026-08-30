<?php

declare(strict_types=1);

namespace App\Domain\Planning\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salle extends Model
{
    use HasTenantAudit;

    protected $table = 'salle';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'nom',
        'capacite',
    ];

    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class, 'salle_id');
    }

    public function examens(): HasMany
    {
        return $this->hasMany(Examen::class, 'salle_id');
    }
}
