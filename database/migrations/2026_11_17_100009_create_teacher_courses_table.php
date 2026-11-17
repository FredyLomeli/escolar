<?php

use App\Enums\TeacherCourseSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('source', 32)->default(TeacherCourseSource::Manual->value);
            $table->timestamps();

            $table->unique(['teacher_id', 'course_id']);
        });
    }
};
