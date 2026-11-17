<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('modality_id')->constrained('modalities')->restrictOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }
};
