<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_term_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->unsignedTinyInteger('term_number');
            $table->unsignedTinyInteger('expected_courses');
            $table->timestamps();

            $table->unique(['curriculum_id', 'term_number']);
        });
    }
};
