<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel'); // mail, whatsapp
            $table->string('recipient')->nullable();
            $table->string('notification'); // notification class basename
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('status')->default('logged'); // logged, stubbed_no_provider
            $table->timestamps();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
        Schema::dropIfExists('notification_logs');
    }
};
