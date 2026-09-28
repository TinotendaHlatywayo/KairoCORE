<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            // A fixed asset is not a store catalog item: the asset now carries
            // its own name and description.
            $table->string('asset_name')->nullable()->after('inventory_item_id');
            $table->text('description')->nullable()->after('asset_name');

            // Useful life is optional now, so blank must be storable rather
            // than a NOT NULL violation.
            $table->integer('useful_life_years')->unsigned()->nullable()->default(null)->change();

            // current_value had no default and no form field, so creating an
            // asset through the UI failed with a missing-value SQL error.
            $table->decimal('current_value', 15, 2)->nullable()->default(null)->change();
        });

        // The catalog link becomes optional. Its FK is re-pointed from CASCADE
        // to SET NULL so deleting a catalog item can no longer delete an asset
        // that merely referenced it.
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
        });

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')->nullable()->change();
        });

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('set null');
        });

        $this->backfillAssetNames();
    }

    public function down(): void
    {
        // Rows created without a useful life or a current value cannot satisfy
        // the original NOT NULL constraints, so only restore those when the
        // table can take them.
        if (DB::table('fixed_assets')->whereNull('useful_life_years')->exists()) {
            DB::table('fixed_assets')->whereNull('useful_life_years')->update(['useful_life_years' => 0]);
        }

        if (DB::table('fixed_assets')->whereNull('current_value')->exists()) {
            DB::table('fixed_assets')->whereNull('current_value')->update([
                'current_value' => DB::raw('purchase_cost'),
            ]);
        }

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropColumn(['asset_name', 'description']);

            $table->integer('useful_life_years')->unsigned()->default(0)->change();
            $table->decimal('current_value', 15, 2)->change();
        });

        // Assets provisioned without a catalog link cannot satisfy the original
        // NOT NULL constraint, so only restore it when every row has a link.
        if (DB::table('fixed_assets')->whereNull('inventory_item_id')->exists()) {
            return;
        }

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
        });

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')->nullable(false)->change();
        });

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->foreign('inventory_item_id')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('cascade');
        });
    }

    /**
     * Give every existing asset its own name, taken from the catalog item it
     * was linked to before this change.
     */
    protected function backfillAssetNames(): void
    {
        DB::table('fixed_assets')
            ->whereNull('asset_name')
            ->whereNotNull('inventory_item_id')
            ->update([
                'asset_name' => DB::raw(
                    '(SELECT i.name FROM inventory_items i WHERE i.id = fixed_assets.inventory_item_id)'
                ),
            ]);
    }
};
