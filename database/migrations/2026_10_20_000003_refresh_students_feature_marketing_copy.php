<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The stored marketing `features_list` override is merged positionally over
 * the shipped defaults, so an old saved copy keeps showing the retired
 * "Student profiles" wording even after the defaults were updated. Patch the
 * stored Students entry (matched by name) to the new Student portal copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->patchStoredList();
    }

    public function down(): void
    {
        // Irreversible content refresh; nothing sensible to restore.
    }

    private function patchStoredList(): void
    {
        try {
            $rows = DB::table('platform_settings')
                ->where('group', 'marketing')
                ->where('key', 'features_list')
                ->get();
        } catch (\Throwable $e) {
            return; // Table missing (fresh install) — defaults apply as-is.
        }

        foreach ($rows as $row) {
            $list = json_decode((string) $row->value, true);
            if (! is_array($list)) {
                continue;
            }

            $changed = false;
            foreach ($list as $i => $item) {
                if (($item['name'] ?? null) !== 'Students') {
                    continue;
                }

                $list[$i]['desc'] = 'Student portal for learning materials, homework, fees and results.';
                $list[$i]['detail'] = 'Every learner gets a secure portal login to access learning materials, submit homework, manage fees, view reports and assessment results, browse the library catalogue and track performance analytics — alongside a complete profile with guardian contacts, medical notes and attendance history.';
                $list[$i]['sub1'] = 'Student portal';
                $changed = true;
            }

            if ($changed) {
                DB::table('platform_settings')->where('id', $row->id)->update([
                    'value' => json_encode($list),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
