<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_estimated_income', 12, 2)->default(0);
            $table->decimal('total_estimated_cost', 12, 2)->default(0);
            $table->decimal('total_actual_cost', 12, 2)->default(0);
            $table->decimal('contingency_percent', 5, 2)->default(5.00);
            $table->decimal('contingency_amount', 12, 2)->default(0);
            $table->string('status')->default('draft'); // draft, approved, active, locked
            $table->timestamps();
        });

        Schema::create('event_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_budget_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // venue, catering, decoration, audio_visual, photography, entertainment, staff, logistics
            $table->string('item_name');
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->decimal('actual_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('event_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->string('expense_number')->nullable();
            $table->string('title');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('incurred_date');
            $table->string('payment_method')->default('bank_transfer'); // cash, bank_transfer, card, upi, cheque
            $table->string('receipt_number')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status')->default('approved'); // pending, approved, paid, rejected
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_code')->nullable();
            $table->string('name');
            $table->integer('total_quantity')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->string('unit')->default('pcs'); // pcs, sets, meters, boxes
            $table->decimal('rental_rate', 12, 2)->default(0);
            $table->decimal('replacement_cost', 12, 2)->default(0);
            $table->string('condition')->default('good'); // good, fair, maintenance_required
            $table->timestamps();
        });

        Schema::create('inventory_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_allocated')->default(1);
            $table->dateTime('checkout_time')->nullable();
            $table->dateTime('return_time')->nullable();
            $table->string('status')->default('allocated'); // allocated, checked_out, returned, damaged
            $table->timestamps();
        });

        Schema::create('catering_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('menu_name');
            $table->string('meal_type')->default('dinner'); // breakfast, lunch, high_tea, dinner, cocktail
            $table->decimal('per_plate_cost', 10, 2)->default(0);
            $table->integer('expected_plates')->default(0);
            $table->longText('menu_items')->nullable(); // JSON or text of appetizers, main course, desserts
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });

        Schema::create('decoration_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('theme_name');
            $table->string('color_palette')->nullable();
            $table->text('floral_requirements')->nullable();
            $table->text('lighting_concept')->nullable();
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->string('status')->default('approved');
            $table->timestamps();
        });

        Schema::create('staff_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->references('id')->on('employees')->nullOnDelete();
            $table->string('role_title');
            $table->date('shift_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('attendance_status')->default('scheduled'); // scheduled, present, late, absent
            $table->decimal('allowance', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_shifts');
        Schema::dropIfExists('decoration_themes');
        Schema::dropIfExists('catering_menus');
        Schema::dropIfExists('inventory_allocations');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('event_expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('event_budget_items');
        Schema::dropIfExists('event_budgets');
    }
};

