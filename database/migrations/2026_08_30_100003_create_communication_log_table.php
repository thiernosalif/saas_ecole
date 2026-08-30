<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_log', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('type', 20); // EMAIL, SMS, ANNONCE
            $table->text('destinataires');
            $table->string('sujet', 200)->nullable();
            $table->text('contenu');
            $table->string('envoye_par', 100)->nullable();
            $table->timestampTz('envoye_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_log');
    }
};
