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
        Schema::create('matiere', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->string('libelle', 100);
            $table->string('code', 20)->nullable();
            $table->decimal('coefficient', 4, 1)->default(1.0);
            $table->foreignUuid('niveau_id')->nullable()->constrained('niveau');
            $table->string('domaine', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matiere');
    }
};
