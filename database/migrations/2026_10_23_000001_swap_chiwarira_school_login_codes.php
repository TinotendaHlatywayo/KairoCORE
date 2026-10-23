<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The school that is open keeps the plain CHIWARI-PRI code; the suspended
     * duplicate takes CHIWARI-PRI2. The codes are parked first because
     * login_code is unique, so they cannot simply be swapped in place.
     */
    public function up(): void
    {
        $this->assign('CHIWARI-PRI', 'CHIWARI-PRI2');
    }

    public function down(): void
    {
        $this->assign('CHIWARI-PRI2', 'CHIWARI-PRI');
    }

    private function assign(string $activeCode, string $suspendedCode): void
    {
        $schools = DB::table('schools')
            ->whereNull('deleted_at')
            ->where('name', 'like', 'Chiwarira Primary School%')
            ->whereIn('login_code', ['CHIWARI-PRI', 'CHIWARI-PRI2', $activeCode, $suspendedCode])
            ->get(['id', 'status']);

        if ($schools->isEmpty()) {
            return;
        }

        foreach ($schools as $school) {
            DB::table('schools')
                ->where('id', $school->id)
                ->update(['login_code' => 'TMP-'.$school->id]);
        }

        $active = $schools->firstWhere('status', 'active');
        $suspended = $schools->firstWhere('status', 'suspended');

        if ($active) {
            DB::table('schools')->where('id', $active->id)->update(['login_code' => $activeCode]);
        }

        if ($suspended) {
            DB::table('schools')->where('id', $suspended->id)->update(['login_code' => $suspendedCode]);
        }

        // Nothing else is expected to share these names, but if it does, give
        // it a code of its own rather than leaving it parked on a temporary one.
        $assigned = array_filter([$active->id ?? null, $suspended->id ?? null]);

        foreach ($schools as $school) {
            if (in_array($school->id, $assigned, true)) {
                continue;
            }

            DB::table('schools')
                ->where('id', $school->id)
                ->update(['login_code' => 'CHIWARI-'.$school->id]);
        }
    }
};
