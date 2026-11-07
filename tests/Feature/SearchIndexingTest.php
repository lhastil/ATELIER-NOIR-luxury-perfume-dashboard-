<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchIndexingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_not_indexable(): void
    {
        $this->get('/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_redirect_for_guests_is_not_indexable(): void
    {
        $this->get('/')->assertRedirect('/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_missing_pages_are_not_indexable(): void
    {
        $this->get('/does-not-exist')->assertNotFound()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_admin_pages_are_not_indexable(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
