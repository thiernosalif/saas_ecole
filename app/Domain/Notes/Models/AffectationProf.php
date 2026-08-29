<?php

declare(strict_types=1);

namespace App\Domain\Notes\Models;

use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Personne;
use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffectationProf extends Model
{
    use HasTenantAudit;

    protected $table = 'affectation_prof';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'prof_id',
        'classe_id',
        'matiere_id',
        'annee_id',
    ];

    public function prof(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'prof_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_id');
    }
}
