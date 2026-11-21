<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->string('holder_name');
            $table->string('holder_email')->nullable();
            $table->string('holder_phone')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('razorpay_order_id')->unique();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('status')->default('created'); // created, paid, expired, failed
            $table->dateTime('expires_at');
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('booking_order_id')->nullable()->after('ticket_type_id')
                ->constrained('booking_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_order_id');
        });
        Schema::dropIfExists('booking_orders');
    }
};
