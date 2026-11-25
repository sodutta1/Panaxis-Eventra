<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['leads', 'clients', 'venues', 'vendors', 'event_guests', 'events', 'quotations'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->softDeletes());
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->dropSoftDeletes());
        }
    }
};
