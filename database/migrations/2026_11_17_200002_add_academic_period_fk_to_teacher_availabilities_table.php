<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_availabilities', function (Blueprint $table): void {
            $table->foreign('academic_period_id')
                ->references('id')
                ->on('academic_periods')
                ->nullOnDelete();
        });
    }
};
