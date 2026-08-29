<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LienParente extends Model
{
    use HasTenantAudit;

    protected $table = 'lien_parente';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'eleve_id',
        'parent_id',
        'lien',
        'tuteur_principal',
        'contact_urgence',
    ];

    protected function casts(): array
    {
        return [
            'tuteur_principal' => 'boolean',
            'contact_urgence' => 'boolean',
        ];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'eleve_id');
    }

    public function parentPersonne(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'parent_id');
    }
}
