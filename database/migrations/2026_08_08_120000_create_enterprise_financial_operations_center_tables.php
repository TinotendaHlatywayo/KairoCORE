<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Chart of Accounts
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('code'); // e.g., 1000 for Cash, 4000 for Tuition Revenue
            $table->string('name');
            $table->string('type'); // asset, liability, equity, revenue, expense
            $table->string('normal_balance')->default('debit'); // debit or credit
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });

        // 2. Journal Entries (Double-Entry Ledger)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->date('entry_date');
            $table->string('reference_number')->nullable();
            $table->text('narration');
            $table->string('status')->default('posted'); // draft, posted, void
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // created by
            $table->timestamps();
        });

        // 3. Journal Line Items
        Schema::create('journal_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('memo')->nullable();
            $table->timestamps();
        });

        // 4. Unlimited Revenue Categories & Streams
        Schema::create('revenue_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g., Tuition, Transport, School Shop
            $table->text('description')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null'); // Default revenue ledger account
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('revenue_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('revenue_category_id')->constrained('revenue_categories')->onDelete('cascade');
            $table->string('name'); // e.g., Bus Route A, Uniform Sales, Grade 1 Tuition
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Suppliers / Vendors
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->timestamps();
        });

        // 6. Expense Categories & Types
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g., Laboratory, Maintenance, Utilities
            $table->text('description')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('expense_category_id')->constrained('expense_categories')->onDelete('cascade');
            $table->string('name'); // e.g., Hydrochloric Acid, Fuel, Electricity
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->timestamps();
        });

        // 7. Purchase Requests & Expenses
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->decimal('estimated_amount', 15, 2);
            $table->string('status')->default('pending'); // pending, approved, rejected, ordered, paid
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('expense_type_id')->constrained('expense_types')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('approved'); // pending, approved, paid
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('expense_types');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('revenue_streams');
        Schema::dropIfExists('revenue_categories');
        Schema::dropIfExists('journal_line_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
