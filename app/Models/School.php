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
        'locale',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'has_dummy_data' => 'boolean',
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
            $tables = DB::table('information_schema.columns')
                ->where('table_schema', DB::connection()->getDatabaseName())
                ->where('column_name', 'school_id')
                ->pluck('table_name');

            foreach ($tables as $table) {
                try {
                    DB::table($table)->where('school_id', $school->id)->delete();
                } catch (\Throwable $e) {
                    report($e);
                }
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
