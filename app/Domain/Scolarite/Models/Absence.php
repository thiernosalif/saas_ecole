<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    use HasTenantAudit;

    public const TYPE_ABSENCE = 'ABSENCE';

    public const TYPE_RETARD = 'RETARD';

    protected $table = 'absence';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'eleve_id',
        'matiere_id',
        'date',
        'heure_debut',
        'heure_fin',
        'type',
        'justifiee',
        'justificatif',
        'saisie_par',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'justifiee' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'eleve_id');
    }

    public function saisiePar(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'saisie_par');
    }
}
