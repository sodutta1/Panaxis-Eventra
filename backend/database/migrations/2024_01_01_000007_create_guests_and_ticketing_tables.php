<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // VIP, Family, Corporate Delegates, Media, General
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('event_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('invitation_code')->unique();
            $table->string('rsvp_status')->default('pending'); // pending, attending, declined, tentative
            $table->integer('plus_ones')->default(0);
            $table->string('table_number')->nullable();
            $table->string('dietary_preference')->default('standard'); // veg, non_veg, vegan, jain, gluten_free
            $table->boolean('checked_in')->default(false);
            $table->dateTime('checked_in_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // General Admission, VIP Pass, Early Bird, Student, Backstage
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('total_capacity')->default(100);
            $table->integer('sold_count')->default(0);
            $table->text('benefits')->nullable();
            $table->dateTime('sales_start')->nullable();
            $table->dateTime('sales_end')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('qr_token')->unique();
            $table->string('holder_name');
            $table->string('holder_email')->nullable();
            $table->string('holder_phone')->nullable();
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->string('payment_status')->default('paid'); // paid, refunded, complimentary
            $table->string('ticket_status')->default('valid'); // valid, checked_in, cancelled
            $table->dateTime('checked_in_at')->nullable();
            $table->string('checked_in_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_types');
        Schema::dropIfExists('event_guests');
        Schema::dropIfExists('guest_groups');
    }
};

