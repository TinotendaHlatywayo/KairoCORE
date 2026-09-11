<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inventory items: track when a lot/item was first received and allow
        // manual date entry via the calendar date picker in the form.
        if (! Schema::hasColumn('inventory_items', 'received_date')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->date('received_date')->nullable()->after('description');
            });

            DB::table('inventory_items')
                ->whereNull('received_date')
                ->orderBy('id')
                ->each(function ($item) {
                    DB::table('inventory_items')
                        ->where('id', $item->id)
                        ->update(['received_date' => substr((string) $item->created_at, 0, 10) ?: now()->toDateString()]);
                });
        }

        // Procurement requests: purpose, approval trail and signature fields.
        Schema::table('procurement_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('procurement_requests', 'purpose')) {
                $table->text('purpose')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('procurement_requests', 'approved_by_id')) {
                $table->foreignId('approved_by_id')->nullable()->after('requester_id')->constrained('users')->onDelete('set null');
            }
            if (! Schema::hasColumn('procurement_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by_id');
            }
            if (! Schema::hasColumn('procurement_requests', 'requester_signature')) {
                $table->string('requester_signature', 255)->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('procurement_requests', 'officer_signature')) {
                $table->string('officer_signature', 255)->nullable()->after('requester_signature');
            }
            if (! Schema::hasColumn('procurement_requests', 'date_signed')) {
                $table->date('date_signed')->nullable()->after('officer_signature');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'received_date')) {
                $table->dropColumn('received_date');
            }
        });

        Schema::table('procurement_requests', function (Blueprint $table) {
            foreach (['date_signed', 'officer_signature', 'requester_signature', 'approved_at', 'approved_by_id', 'purpose'] as $column) {
                if (Schema::hasColumn('procurement_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};