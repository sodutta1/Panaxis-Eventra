<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('response_source', 20)->nullable(); // client_portal | staff_recorded
            $table->string('response_method', 20)->nullable(); // verbal | phone | in_person | written
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['response_source', 'response_method']);
        });
    }
};
