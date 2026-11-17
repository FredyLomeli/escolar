<?php

use App\Enums\StudentGroupStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->restrictOnDelete();
            $table->foreignId('curriculum_id')->constrained('curricula')->restrictOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->foreignId('entry_period_id')->constrained('academic_periods')->restrictOnDelete();
            $table->string('label', 100)->nullable();
            $table->string('status', 32)->default(StudentGroupStatus::Active->value)->index();
            $table->timestamps();
        });
    }
};
