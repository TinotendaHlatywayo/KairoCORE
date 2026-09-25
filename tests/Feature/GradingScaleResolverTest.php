<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\GradingPoint;
use Modules\Academics\Models\GradingScale;
use Modules\Academics\Services\GradingScaleResolver;
use Tests\TestCase;

class GradingScaleResolverTest extends TestCase
{
    use InteractsWithDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        $this->school = School::where('subdomain', 'chiwariraprimary')->first() ?? School::first();
        $this->assertNotNull($this->school, 'A school record is required.');

        GradingScaleResolver::flushCache();
    }

    public function test_fallback_bands_are_used_when_no_scale_is_configured(): void
    {
        $this->assertSame('A', GradingScaleResolver::rating(85, null)['symbol']);
        $this->assertSame('B', GradingScaleResolver::rating(72, null)['symbol']);
        $this->assertSame('C', GradingScaleResolver::rating(60, null)['symbol']);
        $this->assertSame('D', GradingScaleResolver::rating(55, null)['symbol']);
        $this->assertSame('E', GradingScaleResolver::rating(44.5, null)['symbol']);
        $this->assertSame('U', GradingScaleResolver::rating(39, null)['symbol']);

        $key = GradingScaleResolver::key(null);
        $this->assertSame('80-100', $key['A']);
        $this->assertNull(GradingScaleResolver::rating(null, null));

        $this->assertSame('success', GradingScaleResolver::tone(null, 'A'));
        $this->assertSame('danger', GradingScaleResolver::tone(null, 'U'));
        $this->assertSame('warning', GradingScaleResolver::tone(null, 'D'));
    }

    public function test_configured_grade_scale_is_honoured(): void
    {
        $tag = uniqid('GRD_', true);

        try {
            $scale = GradingScale::withoutGlobalScopes()->create([
                'school_id' => $this->school->id,
                'name' => $tag.' Scale',
            ]);

            $points = [
                ['symbol' => '1', 'min_score' => 90, 'max_score' => 100, 'remark' => 'Excellent'],
                ['symbol' => '2', 'min_score' => 75, 'max_score' => 89.99, 'remark' => 'Very Good'],
                ['symbol' => '3', 'min_score' => 50, 'max_score' => 74.99, 'remark' => 'Pass'],
                ['symbol' => '9', 'min_score' => 0, 'max_score' => 49.99, 'remark' => 'Fail'],
            ];

            foreach ($points as $point) {
                GradingPoint::withoutGlobalScopes()->create(array_merge($point, ['grading_scale_id' => $scale->id]));
            }

            $this->assertSame('1', GradingScaleResolver::rating(95, (int) $this->school->id)['symbol']);
            $this->assertSame('2', GradingScaleResolver::rating(85, (int) $this->school->id)['symbol']);
            $this->assertSame('3', GradingScaleResolver::rating(60, (int) $this->school->id)['symbol']);
            $this->assertSame('9', GradingScaleResolver::rating(30, (int) $this->school->id)['symbol']);

            $key = GradingScaleResolver::key((int) $this->school->id);
            $this->assertSame('90-100', $key['1']);
            $this->assertSame('75-90', $key['2']);
        } finally {
            GradingScale::withoutGlobalScopes()->where('name', 'like', 'GRD_%')->delete();
        }
    }
}