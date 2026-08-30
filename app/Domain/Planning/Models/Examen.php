<?php

declare(strict_types=1);

namespace App\Domain\Planning\Models;

use App\Domain\Notes\Models\Matiere;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Trimestre;
use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Examen extends Model
{
    use HasTenantAudit;

    protected $table = 'examen';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'classe_id',
        'matiere_id',
        'trimestre_id',
        'salle_id',
        'date_examen',
        'heure_debut',
        'duree_minutes',
        'bareme',
        'libelle',
    ];

    protected function casts(): array
    {
        return [
            'date_examen' => 'date',
            'bareme' => 'decimal:2',
        ];
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

    public function trimestre(): BelongsTo
    {
        return $this->belongsTo(Trimestre::class, 'trimestre_id');
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class, 'salle_id');
    }
}
