<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module', 80);
            $table->string('name', 80);
            $table->string('query', 1000)->default('');
            $table->timestamps();
            $table->unique(['user_id', 'module', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_views');
    }
};
