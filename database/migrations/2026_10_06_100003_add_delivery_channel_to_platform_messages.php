<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the super admin choose, per message, whether it is delivered as an
 * in-app platform message, as an email to the address that registered the
 * school, or both.
 *
 * delivery_receipts records whether the email leg was actually dispatched so a
 * failed send is visible rather than silently swallowed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_messages', 'channel')) {
                $table->string('channel', 30)->default('platform_message')->after('priority');
            }
            if (! Schema::hasColumn('platform_messages', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('channel');
            }
            if (! Schema::hasColumn('platform_messages', 'email_error')) {
                $table->text('email_error')->nullable()->after('email_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_messages', function (Blueprint $table) {
            foreach (['channel', 'email_sent_at', 'email_error'] as $column) {
                if (Schema::hasColumn('platform_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};