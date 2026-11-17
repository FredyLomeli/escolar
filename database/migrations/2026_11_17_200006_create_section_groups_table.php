<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('group_term_id')->constrained('group_terms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['section_id', 'group_term_id']);
        });
    }
};
