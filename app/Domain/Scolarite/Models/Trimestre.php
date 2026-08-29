<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trimestre extends Model
{
    use HasTenantAudit;

    protected $table = 'trimestre';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'annee_id',
        'numero',
        'date_debut',
        'date_fin',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_id');
    }
}
