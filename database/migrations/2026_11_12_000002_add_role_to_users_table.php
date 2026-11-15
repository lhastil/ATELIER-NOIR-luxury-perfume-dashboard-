<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each account gets a role: store_manager, admin or seller (see User::ROLES).
 *
 * Accounts that exist before this migration had full access to the panel, so
 * they become Store Managers and keep it; this also guarantees the panel has a
 * Store Manager. Rows added later without a role (for example inserted by hand)
 * default to the most limited role, seller.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('seller');
        });

        DB::table('users')->update(['role' => 'store_manager']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
