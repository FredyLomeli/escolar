<?php

use App\Enums\DeliveryType;
use App\Enums\SectionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->string('crn', 32)->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('delivery', 32)->default(DeliveryType::InPerson->value);
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->unsignedTinyInteger('start_week');
            $table->unsignedTinyInteger('end_week');
            $table->unsignedSmallInteger('students_override')->nullable();
            $table->string('status', 32)->default(SectionStatus::Draft->value)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['academic_period_id', 'crn']);
            $table->index(['academic_period_id', 'teacher_id']);
        });
    }
};
