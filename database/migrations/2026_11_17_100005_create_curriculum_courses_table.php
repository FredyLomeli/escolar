<?php

use App\Enums\CourseKind;
use App\Enums\DeliveryType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->unsignedTinyInteger('term_number');
            $table->string('kind', 32)->default(CourseKind::Regular->value);
            $table->string('default_delivery', 32)->default(DeliveryType::InPerson->value);
            $table->decimal('weekly_hours', 4, 1)->nullable();
            $table->string('display_name', 200)->nullable();
            $table->timestamps();

            $table->unique(['curriculum_id', 'course_id']);
            $table->index(['curriculum_id', 'term_number']);
        });
    }
};
