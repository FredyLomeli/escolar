<?php

use App\Enums\AcademicPeriodStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 100);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('term_of_year');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedTinyInteger('weeks');
            $table->string('status', 32)->default(AcademicPeriodStatus::Draft->value)->index();
            $table->timestamps();
        });
    }
};
