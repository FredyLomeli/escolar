<?php

use App\Enums\TeacherOrigin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table): void {
            $table->id();
            $table->string('employee_id', 32)->unique();
            $table->string('full_name', 200);
            $table->string('email', 150)->nullable();
            $table->string('origin', 32)->default(TeacherOrigin::Campus->value);
            $table->unsignedTinyInteger('max_weekly_hours')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }
};
