<?php

declare(strict_types=1);

namespace App\Domain\Notes\Models;

use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Models\Trimestre;
use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bulletin extends Model
{
    use HasTenantAudit;

    protected $table = 'bulletin';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'eleve_id',
        'trimestre_id',
        'moyenne_generale',
        'rang',
        'effectif_classe',
        'mention',
        'appreciation_dir',
        'nb_absences_justifiees',
        'nb_absences_non_justifiees',
        'url_pdf',
        'valide',
        'genere_at',
    ];

    protected function casts(): array
    {
        return [
            'moyenne_generale' => 'decimal:2',
            'valide' => 'boolean',
            'genere_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'eleve_id');
    }

    public function trimestre(): BelongsTo
    {
        return $this->belongsTo(Trimestre::class, 'trimestre_id');
    }
}
