<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The migration that adds roles. It changes the schema, so each test gets a
 * freshly migrated database.
 */
class RoleMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_existing_accounts_become_store_managers_and_new_rows_default_to_seller(): void
    {
        $this->migration()->down();
        $this->assertFalse(Schema::hasColumn('users', 'role'));

        foreach (['one', 'two', 'three'] as $name) {
            DB::table('users')->insert(['name' => $name, 'email' => "{$name}@example.com", 'password' => 'hash']);
        }

        $this->migration()->up();

        // Every existing account keeps the full access it had.
        $this->assertSame(['store_manager'], DB::table('users')->distinct()->pluck('role')->all());
        $this->assertSame(3, DB::table('users')->count());

        // A row inserted later without a role gets the most limited one.
        DB::table('users')->insert(['name' => 'later', 'email' => 'later@example.com', 'password' => 'hash']);
        $this->assertSame('seller', DB::table('users')->where('email', 'later@example.com')->value('role'));
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_12_000002_add_role_to_users_table.php');
    }
}
