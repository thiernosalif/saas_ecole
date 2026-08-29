<?php

declare(strict_types=1);

namespace App\Domain\Notes\Models;

use App\Domain\Scolarite\Models\Niveau;
use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matiere extends Model
{
    use HasTenantAudit;

    protected $table = 'matiere';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'libelle',
        'code',
        'coefficient',
        'niveau_id',
        'domaine',
    ];

    protected function casts(): array
    {
        return [
            'coefficient' => 'decimal:1',
        ];
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'matiere_id');
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationProf::class, 'matiere_id');
    }
}
