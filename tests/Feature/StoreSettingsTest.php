<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_or_submit_the_settings_form(): void
    {
        $this->get(route('settings.edit'))->assertRedirect(route('login'));
        $this->patch(route('settings.update'), $this->validData())->assertRedirect(route('login'));

        $this->assertDatabaseCount('store_settings', 0);
    }

    public function test_signed_in_users_can_view_the_settings_page(): void
    {
        StoreSetting::create($this->validData());

        $this->actingAs(User::factory()->create())
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('value="Atelier Noir"', false)
            ->assertSee('value="hello@ateliernoir.test"', false)
            ->assertSee('name="logo"', false);
    }

    public function test_valid_settings_are_saved_to_a_single_record(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('settings.update'), $this->validData(['contact_email' => 'Hello@AtelierNoir.test']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHas('status', 'The store settings were saved.');

        // Saving again updates the same record instead of adding another.
        $this->actingAs($user)
            ->patch(route('settings.update'), $this->validData(['store_name' => 'Atelier Noir Paris']))
            ->assertRedirect(route('settings.edit'));

        $this->assertDatabaseCount('store_settings', 1);
        $this->assertDatabaseHas('store_settings', [
            'store_name' => 'Atelier Noir Paris',
            'contact_email' => 'hello@ateliernoir.test',
            'phone' => '+33 1 23 45 67 89',
            'address' => '12 Rue du Parfum, Paris',
        ]);
    }

    public function test_required_fields_and_email_format_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('settings.edit'))
            ->patch(route('settings.update'), ['store_name' => '', 'contact_email' => 'not-an-email'])
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHasErrors(['store_name', 'contact_email']);

        $this->assertDatabaseCount('store_settings', 0);
    }

    public function test_invalid_logo_types_and_files_over_2_mb_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $invalidLogos = [
            'wrong type' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
            'too large' => UploadedFile::fake()->image('logo.png')->size(2049),
        ];

        foreach ($invalidLogos as $logo) {
            $this->actingAs($user)
                ->from(route('settings.edit'))
                ->patch(route('settings.update'), $this->validData(['logo' => $logo]))
                ->assertRedirect(route('settings.edit'))
                ->assertSessionHasErrors('logo');
        }

        $this->assertDatabaseCount('store_settings', 0);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_a_valid_logo_is_stored_and_displayed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('settings.update'), $this->validData(['logo' => UploadedFile::fake()->image('logo.png', 300, 300)]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.edit'));

        $settings = StoreSetting::current();
        $this->assertStringStartsWith(StoreSetting::LOGO_DIRECTORY.'/', $settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertSee('src="'.Storage::disk('public')->url($settings->logo_path).'"', false);
    }

    public function test_replacing_the_logo_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('settings.update'), $this->validData(['logo' => UploadedFile::fake()->image('first.png')]));
        $firstPath = StoreSetting::current()->logo_path;

        // Saving without a new file keeps the current logo.
        $this->actingAs($user)->patch(route('settings.update'), $this->validData());
        $this->assertSame($firstPath, StoreSetting::current()->logo_path);
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($user)->patch(route('settings.update'), $this->validData(['logo' => UploadedFile::fake()->image('second.webp')]));
        $secondPath = StoreSetting::current()->logo_path;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'store_name' => 'Atelier Noir',
            'contact_email' => 'hello@ateliernoir.test',
            'phone' => '+33 1 23 45 67 89',
            'address' => '12 Rue du Parfum, Paris',
        ], $overrides);
    }
}
