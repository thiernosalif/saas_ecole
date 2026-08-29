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
        Schema::create('devoir', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 50);
            $table->foreignUuid('classe_id')->constrained('classe');
            $table->foreignUuid('matiere_id')->constrained('matiere');
            $table->foreignUuid('prof_id')->nullable()->constrained('personne');
            $table->string('titre', 200);
            $table->text('description')->nullable();
            $table->date('date_remise');
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devoir');
    }
};
