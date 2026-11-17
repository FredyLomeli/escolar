<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_code', 16);
            $table->string('course_code', 16);
            $table->string('name', 200);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['subject_code', 'course_code']);
        });
    }
};
