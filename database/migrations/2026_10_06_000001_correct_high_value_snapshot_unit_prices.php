<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Both inputs are stored on the frozen order. Never infer historical
        // quantities from the mutable source order or alter settlement facts.
        DB::table('frozen_orders')
            ->whereNotNull('custom_price')
            ->where('snapshot_quantity', '>', 0)
            ->update([
                'snapshot_unit_price' => DB::raw('ROUND(custom_price / (snapshot_quantity * 1.0), 6)'),
            ]);
    }

    public function down(): void
    {
        // The incorrect source prices cannot be safely reconstructed.
    }
};
