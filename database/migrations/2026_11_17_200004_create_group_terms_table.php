<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_terms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_group_id')->constrained('student_groups')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->unsignedTinyInteger('term_number');
            $table->unsignedSmallInteger('student_count')->default(0);
            $table->boolean('student_count_confirmed')->default(false);
            $table->timestamps();

            $table->unique(['student_group_id', 'academic_period_id']);
            $table->index('academic_period_id');
        });
    }
};
