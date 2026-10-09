<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Staff records are provisioned by an administrator from the Staff
     * Directory & HR screen, so their portal accounts are already approved and
     * must not sit in the "Pending Approval" queue. Provisioning used to force
     * every account back to pending (and the "Send Activation Email" bulk
     * action silently demoted active staff), so this repairs accounts that are
     * still linked to a live employee record.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('account_status', 'pending')
            ->whereNull('deleted_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('employees')
                    ->whereColumn('employees.user_id', 'users.id')
                    ->whereNull('employees.deleted_at');
            })
            ->update(['account_status' => 'active']);
    }

    public function down(): void
    {
        // Irreversible: the previous pending state cannot be distinguished
        // from a legitimate one, and re-locking staff would be worse.
    }
};
