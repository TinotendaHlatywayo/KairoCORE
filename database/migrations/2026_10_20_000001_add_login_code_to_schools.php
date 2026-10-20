<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('login_code', 24)
                ->after('subdomain')
                ->nullable()
                ->unique();
        });

        // Backfill: every existing school gets a memorable short code derived
        // from its name + institution type (e.g. TINWAY-SEC), retrying with a
        // numeric suffix on collision.
        $used = DB::table('schools')->whereNotNull('login_code')->pluck('login_code')->all();

        DB::table('schools')->whereNull('login_code')->orderBy('id')->get()
            ->each(function (object $school) use (&$used): void {
                $base = self::baseCode($school->name, $school->institution_type);
                $code = $base;
                $n = 2;
                while (in_array($code, $used, true)) {
                    $code = substr($base, 0, 22).$n;
                    $n++;
                }
                $used[] = $code;

                DB::table('schools')->where('id', $school->id)->update(['login_code' => $code]);
            });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropUnique(['login_code']);
            $table->dropColumn('login_code');
        });
    }

    private static function baseCode(?string $name, ?string $type): string
    {
        $firstWord = explode(' ', trim((string) $name))[0] ?? '';
        $word = strtoupper(preg_replace('/[^A-Za-z]/', '', $firstWord) ?: 'SCHOOL');
        $segment = substr($word, 0, 7);
        if (strlen($segment) < 3) {
            $segment = substr($word, 0, 7).'SCH';
        }

        $typeMap = [
            'primary' => 'PRI',
            'secondary' => 'SEC',
            'college' => 'COL',
            'university' => 'UNI',
            'academy' => 'ACA',
            'nursery' => 'NUR',
            'international' => 'INT',
        ];
        $typeCode = $typeMap[strtolower(trim((string) $type))] ?? 'SCH';

        return substr($segment.'-'.$typeCode, 0, 24);
    }
};
