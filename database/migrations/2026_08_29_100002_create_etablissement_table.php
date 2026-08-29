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
        Schema::create('etablissement', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50)->unique();
            $table->string('nom', 200);
            $table->string('sous_domaine', 50)->unique();
            $table->text('logo_url')->nullable();
            $table->text('adresse')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->foreignUuid('plan_id')->nullable()->constrained('plan_tarifaire');
            $table->string('statut', 20)->default('ACTIF');
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissement');
    }
};
