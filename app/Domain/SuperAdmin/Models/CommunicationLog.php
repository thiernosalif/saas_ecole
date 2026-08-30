<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CommunicationLog extends Model
{
    use HasUuids;

    public const TYPE_EMAIL = 'EMAIL';

    public const TYPE_SMS = 'SMS';

    public const TYPE_ANNONCE = 'ANNONCE';

    protected $table = 'communication_log';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'destinataires',
        'sujet',
        'contenu',
        'envoye_par',
    ];

    protected function casts(): array
    {
        return [
            'envoye_at' => 'datetime',
        ];
    }
}
