<?php

use App\Security\RoleCatalogue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Admin\Models\CustomRole;

/**
 * Give the catalogue-managed roles a stable identity, so their default
 * permissions can be kept in step with `RoleCatalogue` forever.
 *
 * Without this, "is this row one of our defaults?" could only be answered by
 * comparing the *name* with a catalogue label. That answer is unreliable in
 * both directions: a catalogue rename would orphan the row and the school
 * would silently keep stale defaults, and a hand-made role an administrator
 * happened to call "Teaching Staff" would be mistaken for ours and
 * overwritten. `role_key` records the answer once, at creation, and after
 * that the identity never depends on a label that can be edited or
 * re-capitalised.
 *
 * `permissions_customised` then records the other half of the question: has an
 * administrator tailored this role since it was created? The synchroniser
 * refreshes the defaults of untouched roles — which is what fixes a school
 * whose Teaching Staff role still carries Publish to Student Portal — and
 * leaves a tailored role exactly as the administrator left it.
 *
 * Adoption is deliberately conservative: only rows already flagged as system
 * roles, whose name matches a catalogue label, are claimed. Anything an
 * administrator created keeps `role_key = null` and is never touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custom_roles')) {
            return;
        }

        if (! Schema::hasColumn('custom_roles', 'role_key')) {
            Schema::table('custom_roles', function (Blueprint $table) {
                $table->string('role_key', 64)->nullable()->after('name');
                $table->index(['school_id', 'role_key'], 'idx_custom_role_key');
            });
        }

        if (! Schema::hasColumn('custom_roles', 'permissions_customised')) {
            Schema::table('custom_roles', function (Blueprint $table) {
                $table->boolean('permissions_customised')->default(false)->after('is_system');
            });
        }

        $this->adoptExistingSystemRoles();
    }

    public function down(): void
    {
        if (! Schema::hasTable('custom_roles')) {
            return;
        }

        if (Schema::hasColumn('custom_roles', 'permissions_customised')) {
            Schema::table('custom_roles', function (Blueprint $table) {
                $table->dropColumn('permissions_customised');
            });
        }

        if (Schema::hasColumn('custom_roles', 'role_key')) {
            Schema::table('custom_roles', function (Blueprint $table) {
                $table->dropIndex('idx_custom_role_key');
                $table->dropColumn('role_key');
            });
        }
    }

    /**
     * Claim the rows that are already ours, in id order so that two rows
     * matching the same label ("Teaching Staff" and "teaching staff") resolve
     * to one owner instead of both claiming the same key.
     */
    private function adoptExistingSystemRoles(): void
    {
        if (! Schema::hasColumn('custom_roles', 'role_key')) {
            return;
        }

        $claimed = [];

        CustomRole::withoutTenantScope()
            ->where('is_system', true)
            ->orderBy('id')
            ->each(function (CustomRole $role) use (&$claimed): void {
                $key = RoleCatalogue::keyForRoleName($role->name);

                if ($key === null) {
                    return;
                }

                if (isset($claimed[$role->school_id][$key])) {
                    return;
                }

                $claimed[$role->school_id][$key] = true;

                $role->forceFill(['role_key' => $key])->saveQuietly();
            });
    }
};
