<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\SaaSSubscription;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'subdomain',
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
