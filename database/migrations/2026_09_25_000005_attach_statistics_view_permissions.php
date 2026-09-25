<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $parent = DB::table('manager_settings')->where('manager_code', 'permission-group.statistics')->whereNull('parent_manager_setting_id')->value('id');
            if (! $parent) {
                throw new RuntimeException('Statistics root group is missing.');
            }
            DB::table('manager_settings')->whereIn('manager_code', [
                'statistics.view-overview', 'statistics.view-staff',
                'statistics.view-customers', 'statistics.view-personal',
            ])->whereNull('parent_manager_setting_id')->update(['parent_manager_setting_id' => $parent]);
        });
    }

    public function down(): void
    {
        // Keep the corrected hierarchy without changing any grants.
    }
};
