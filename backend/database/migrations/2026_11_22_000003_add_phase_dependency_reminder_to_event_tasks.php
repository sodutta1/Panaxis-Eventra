<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->string('phase')->default('pre_event')->after('status'); // pre_event, on_event, post_event
            $table->foreignId('depends_on')->nullable()->after('phase')->references('id')->on('event_tasks')->nullOnDelete();
            $table->dateTime('reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('depends_on');
            $table->dropColumn(['phase', 'reminder_sent_at']);
        });
    }
};
