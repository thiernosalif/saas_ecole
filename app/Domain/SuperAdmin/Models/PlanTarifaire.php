<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanTarifaire extends Model
{
    use HasUuids;

    protected $table = 'plan_tarifaire';

    public $timestamps = false;

    protected $fillable = [
        'nom',
        'prix_mensuel',
        'max_eleves',
        'modules_inclus',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'prix_mensuel' => 'decimal:2',
            'modules_inclus' => 'array',
            'actif' => 'boolean',
        ];
    }

    public function etablissements(): HasMany
    {
        return $this->hasMany(Etablissement::class, 'plan_id');
    }
}
