<?php

declare(strict_types=1);

namespace App\Domain\Planning\Models;

use App\Domain\Notes\Models\Matiere;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Personne;
use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seance extends Model
{
    use HasTenantAudit;

    protected $table = 'seance';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'classe_id',
        'matiere_id',
        'prof_id',
        'salle_id',
        'annee_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
    ];

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

    public function prof(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'prof_id');
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class, 'salle_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_id');
    }
}
