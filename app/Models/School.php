<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\SaaSSubscription;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'subdomain',
        'login_code',
        'status',
        'region',
        'country',
        'motto',
        'phone_number',
        'email_address',
        'website_url',
        'physical_address',
        'language',
        'institution_type',
        'other_institution_type',
        'phone',
        'logo_path',
        'signature_path',
        'stamp_path',
        'trial_ends_at',
        'settings',
        'has_dummy_data',
        'seed_status',
        'seeded_at',
        'seed_error',
        'locale',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'has_dummy_data' => 'boolean',
        'seeded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $school): void {
            if (! $school->login_code) {
                $school->login_code = static::generateLoginCode(
                    $school->name,
                    $school->institution_type
                );
            }
        });

        static::deleting(function (self $school) {
            $school->users()->get()->each->delete();
        });

        static::saved(function (self $school) {
            if ($school->subdomain) {
                Cache::forget("tenant.subdomain:{$school->subdomain}");
            }
        });

        static::deleted(function (self $school) {
            if ($school->subdomain) {
                Cache::forget("tenant.subdomain:{$school->subdomain}");
            }
        });

        static::forceDeleting(function (self $school) {
            // Purge every row referencing this school across the whole schema,
            // so a permanently deleted school leaves no orphaned records
            // (users, students, finances, CMS content, etc.). [2.1]
            // FK checks are off for the purge: child rows can reference each
            // other in chains that do not carry school_id, and one such chain
            // used to abort the whole delete with a 500. Restored in `finally`
            // so a failed purge cannot leave the connection unprotected.
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                // information_schema columns come back with UPPERCASE names
                // (TABLE_NAME) on production MySQL; plucking 'table_name'
                // then reads an undefined property and throws outside the
                // per-table catch, aborting the whole forceDelete. Select the
                // alias explicitly so the row property is always lowercase.
                $tables = array_map(
                    static fn (object $row): string => (string) $row->table_name,
                    DB::select(
                        'SELECT TABLE_NAME AS table_name
                           FROM information_schema.COLUMNS
                          WHERE table_schema = DATABASE()
                            AND column_name = ?',
                        ['school_id']
                    )
                );

                foreach ($tables as $table) {
                    try {
                        DB::table($table)->where('school_id', $school->id)->delete();
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        });
    }

    /**
     * A short, memorable login code derived from the school's name and
     * institution type — e.g. "TINWAY-SEC". Unique across all schools;
     * retries with a numeric suffix on collision.
     */
    public static function generateLoginCode(?string $name, ?string $institutionType, ?int $excludeId = null): string
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
        $typeCode = $typeMap[strtolower(trim((string) $institutionType))] ?? 'SCH';

        $base = substr($segment.'-'.$typeCode, 0, 24);
        $code = $base;
        $n = 2;

        while (! static::isLoginCodeFree($code, $excludeId)) {
            $code = substr($base, 0, 22).$n;
            $n++;
        }

        return $code;
    }

    protected static function isLoginCodeFree(string $code, ?int $excludeId = null): bool
    {
        $query = static::withTrashed()->where('login_code', $code);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * The staff login URL on this school's own subdomain.
     */
    public function loginUrl(): string
    {
        $base = parse_url(config('app.url'), PHP_URL_HOST);

        return 'https://'.$this->subdomain.'.'.$base.'/workspace/login';
    }

    /** Find a school by its short login code (case-insensitive). */
    public static function findByLoginCode(string $code): ?self
    {
        $code = strtoupper(trim($code));

        return static::withTrashed()->where('login_code', $code)->first();
    }

    /**
     * The schools a visitor may sign in to.
     *
     * A school can be found by its short code, by the start of its name, or
     * by any ordered run of its letters — nobody types a school's name
     * exactly, so "chwarira" has to find "Chiwarira Primary School". Only
     * schools that are open for business are offered: a suspended school
     * cannot be signed in to, and a school that is still waiting to be
     * approved has nobody to sign in yet.
     *
     * @return Collection<int, self>
     */
    public static function findForLoginSearch(?string $term, int $limit = 12)
    {
        $term = trim((string) $term);

        $query = static::query()
            ->where('status', 'active')
            ->whereNotNull('subdomain')
            ->where('subdomain', '!=', '');

        if ($term !== '') {
            $pattern = fuzzy_regex($term);

            $query->where(function ($q) use ($term, $pattern) {
                $q->where('login_code', 'LIKE', strtoupper($term).'%')
                    ->orWhere('name', 'LIKE', $term.'%')
                    ->orWhereRaw('name REGEXP ?', [$pattern])
                    ->orWhereRaw('login_code REGEXP ?', [$pattern]);
            });
        }

        return $query->orderBy('name')->limit($limit)->get();
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function saasSubscription()
    {
        return $this->hasOne(SaaSSubscription::class, 'school_id');
    }
}
