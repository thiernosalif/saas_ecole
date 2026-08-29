<?php

declare(strict_types=1);

namespace App\Domain\Notes\Models;

use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Models\Trimestre;
use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasTenantAudit;

    public const TYPE_DEVOIR = 'DEVOIR';

    public const TYPE_COMPOSITION = 'COMPOSITION';

    protected $table = 'note';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'eleve_id',
        'matiere_id',
        'trimestre_id',
        'valeur',
        'coefficient',
        'type',
        'appreciation',
        'saisie_par',
    ];

    protected function casts(): array
    {
        return [
            'valeur' => 'decimal:2',
            'coefficient' => 'decimal:1',
            'created_at' => 'datetime',
        ];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'eleve_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

    public function trimestre(): BelongsTo
    {
        return $this->belongsTo(Trimestre::class, 'trimestre_id');
    }

    public function saisiePar(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'saisie_par');
    }
}
