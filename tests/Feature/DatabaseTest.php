<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_run_and_users_can_be_persisted(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'admin@example.com']);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_user_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->expectException(QueryException::class);

        User::factory()->create(['email' => 'admin@example.com']);
    }
}
