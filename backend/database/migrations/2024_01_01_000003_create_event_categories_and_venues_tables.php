<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('venue_code')->nullable();
            $table->string('type')->default('hotel'); // banquet_hall, hotel, lawn, resort, convention_centre, stadium, auditorium
            $table->integer('capacity')->default(0);
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->decimal('rental_cost_per_day', 12, 2)->default(0);
            $table->text('amenities')->nullable();
            $table->string('status')->default('available'); // available, booked, maintenance, inactive
            $table->timestamps();
        });

        Schema::create('vendor_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor_code')->nullable();
            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->string('payment_terms')->nullable();
            $table->text('bank_details')->nullable();
            $table->text('services_offered')->nullable();
            $table->string('status')->default('active'); // active, inactive, blacklisted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('vendor_categories');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('event_categories');
    }
};

