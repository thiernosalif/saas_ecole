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
        Schema::create('reglement', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('facture_id')->constrained('facture');
            $table->decimal('montant', 12, 2);
            $table->string('moyen_paiement', 30)->nullable();
            $table->string('reference', 100)->nullable();
            $table->timestampTz('paid_at');
            $table->string('statut', 20)->default('CONFIRME');
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglement');
    }
};
