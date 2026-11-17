<?php

use App\Enums\AvailabilityType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_availabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            // La llave foránea hacia academic_periods se añade en Fase 2 al crear dicha tabla.
            $table->unsignedBigInteger('academic_period_id')->nullable()->index();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('type', 32)->default(AvailabilityType::Available->value);
            $table->timestamps();

            $table->index(['teacher_id', 'day_of_week']);
        });
    }
};
