<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->string('event_code')->unique();
            $table->string('title');
            $table->string('event_type')->default('wedding'); // wedding, conference, birthday, concert, festival, exhibition, product_launch, corporate, seminar, private_party
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('expected_guests')->default(0);
            $table->integer('confirmed_guests')->default(0);
            $table->decimal('estimated_budget', 12, 2)->default(0);
            $table->decimal('actual_budget', 12, 2)->default(0);
            $table->decimal('contract_value', 12, 2)->default(0);
            $table->decimal('amount_received', 12, 2)->default(0);
            $table->string('status')->default('enquiry'); // enquiry, proposed, confirmed, in_planning, active, completed, cancelled
            $table->text('theme_concept')->nullable();
            $table->text('description')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('event_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->integer('day_number')->default(1);
            $table->date('date');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('event_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_day_id')->nullable()->constrained()->nullOnDelete();
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('title');
            $table->string('location_within_venue')->nullable();
            $table->string('speaker_performer')->nullable();
            $table->text('activity_description')->nullable();
            $table->string('in_charge_person')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, delayed
            $table->timestamps();
        });

        Schema::create('event_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('status')->default('pending'); // pending, in_progress, completed, blocked
            $table->timestamps();
        });

        Schema::create('venue_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('booking_start');
            $table->dateTime('booking_end');
            $table->decimal('rental_fee', 12, 2)->default(0);
            $table->decimal('advance_paid', 12, 2)->default(0);
            $table->string('status')->default('confirmed'); // reserved, confirmed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('service_title');
            $table->text('scope_of_work')->nullable();
            $table->decimal('contract_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status')->default('assigned'); // assigned, contracted, in_progress, delivered, completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_assignments');
        Schema::dropIfExists('venue_bookings');
        Schema::dropIfExists('event_tasks');
        Schema::dropIfExists('event_schedules');
        Schema::dropIfExists('event_days');
        Schema::dropIfExists('events');
    }
};

