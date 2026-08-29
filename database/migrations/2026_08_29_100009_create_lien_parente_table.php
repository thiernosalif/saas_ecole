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
        Schema::create('lien_parente', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('eleve_id')->constrained('personne');
            $table->foreignUuid('parent_id')->constrained('personne');
            $table->string('lien', 30)->nullable();
            $table->boolean('tuteur_principal')->default(false);
            $table->boolean('contact_urgence')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lien_parente');
    }
};
