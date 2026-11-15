<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_guests_cannot_manage_profile_photos(): void
    {
        $user = $this->userWithPhoto();
        $path = $user->profile_photo_path;

        $this->put(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('me.jpg')])->assertRedirect(route('login'));
        $this->delete(route('profile.photo.destroy'))->assertRedirect(route('login'));

        $this->assertSame($path, $user->refresh()->profile_photo_path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame([$path], Storage::disk('public')->allFiles());
    }

    public function test_a_photo_can_be_uploaded(): void
    {
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('me.png', 300, 300);

        $this->actingAs($user)
            ->put(route('profile.photo.update'), ['photo' => $photo])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Your profile photo was added.');

        $path = $user->refresh()->profile_photo_path;
        $this->assertStringStartsWith('profile-photos/', $path);
        $this->assertSame($photo->getContent(), Storage::disk('public')->get($path));
        $this->assertSame(Storage::disk('public')->url($path), $user->profile_photo_url);
    }

    public function test_a_new_photo_replaces_the_old_file(): void
    {
        $user = $this->userWithPhoto();
        $other = $this->userWithPhoto();
        $oldPath = $user->profile_photo_path;

        $this->actingAs($user)
            ->put(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('new.webp')])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Your profile photo was replaced.');

        $newPath = $user->refresh()->profile_photo_path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
        // Another user's photo is never touched.
        Storage::disk('public')->assertExists($other->profile_photo_path);
    }

    public function test_a_failed_save_keeps_the_existing_photo(): void
    {
        $user = $this->userWithPhoto();
        $oldPath = $user->profile_photo_path;
        User::saving(fn () => throw new RuntimeException('Database unavailable'));

        $this->actingAs($user)
            ->put(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('new.png')])
            ->assertServerError();

        $this->assertSame($oldPath, $user->refresh()->profile_photo_path);
        // The old file is kept and the new upload removed.
        $this->assertSame([$oldPath], Storage::disk('public')->allFiles());
    }

    public function test_the_photo_can_be_removed(): void
    {
        $user = $this->userWithPhoto();
        $path = $user->profile_photo_path;

        $this->actingAs($user)
            ->delete(route('profile.photo.destroy'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Your profile photo was removed.');

        $this->assertNull($user->refresh()->profile_photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_only_files_in_the_profile_photo_folder_are_deleted(): void
    {
        // A path this page would never store, for example set by hand in the database.
        Storage::disk('public')->put('products/bottle.jpg', 'image');
        $user = User::factory()->create();
        $user->forceFill(['profile_photo_path' => 'products/bottle.jpg'])->save();

        $this->actingAs($user)->delete(route('profile.photo.destroy'))->assertRedirect(route('profile.edit'));

        $this->assertNull($user->refresh()->profile_photo_path);
        Storage::disk('public')->assertExists('products/bottle.jpg');
    }

    public function test_updating_the_profile_without_a_photo_keeps_the_photo(): void
    {
        $user = $this->userWithPhoto();
        $path = $user->profile_photo_path;

        $this->actingAs($user)
            ->patch(route('profile.update'), ['name' => 'New Name', 'email' => 'new@example.com'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Your profile was updated.');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame($path, $user->profile_photo_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_invalid_photos_are_rejected(): void
    {
        $user = $this->userWithPhoto();
        $path = $user->profile_photo_path;

        $invalid = [
            'missing' => null,
            'wrong type' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            'gif' => UploadedFile::fake()->image('me.gif'),
            'too large' => UploadedFile::fake()->image('me.jpg')->size(2049),
        ];

        foreach ($invalid as $photo) {
            $this->actingAs($user)
                ->from(route('profile.edit'))
                ->put(route('profile.photo.update'), $photo ? ['photo' => $photo] : [])
                ->assertRedirect(route('profile.edit'))
                ->assertSessionHasErrors('photo');
        }

        $this->assertSame($path, $user->refresh()->profile_photo_path);
        $this->assertSame([$path], Storage::disk('public')->allFiles());
    }

    public function test_the_profile_page_shows_the_photo_or_the_initials(): void
    {
        $withPhoto = $this->userWithPhoto(['name' => 'Hasti Example']);

        $this->actingAs($withPhoto)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('<form method="POST" action="'.route('profile.photo.update').'" enctype="multipart/form-data"', false)
            ->assertSee('src="'.$withPhoto->profile_photo_url.'"', false)
            ->assertSee('Replace photo')
            ->assertSee('form="remove-photo-form"', false)
            ->assertSee('<form id="remove-photo-form" method="POST" action="'.route('profile.photo.destroy').'" data-confirm="Remove your profile photo?">', false);

        $withoutPhoto = User::factory()->create(['name' => 'Julien Moreau']);

        $this->actingAs($withoutPhoto)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Upload photo')
            ->assertSee('No photo yet')
            ->assertDontSee('remove-photo-form')
            ->assertSee('--avatar-size: 96px', false);
    }

    public function test_the_topbar_and_sidebar_show_the_photo_or_the_initials(): void
    {
        $withPhoto = $this->userWithPhoto(['name' => 'Hasti Example']);
        $html = $this->actingAs($withPhoto)->get(route('dashboard'))->assertOk()->getContent();

        $image = '<img class="avatar__image" src="'.$withPhoto->profile_photo_url.'" alt="" width="34" height="34">';
        $this->assertStringContainsString($image, $html);
        $this->assertStringContainsString('<img class="avatar__image" src="'.$withPhoto->profile_photo_url.'" alt="" width="36" height="36">', $html);
        $this->assertDoesNotMatchRegularExpression('#<span[^>]*class="avatar[^"]*"[^>]*>\s*HE\s*</span>#', $html);

        $withoutPhoto = User::factory()->create(['name' => 'Julien Moreau']);
        $html = $this->actingAs($withoutPhoto)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('avatar__image', $html);
        // Top bar and sidebar: the attribute order is style, aria-hidden, class.
        $this->assertSame(2, preg_match_all('#<span[^>]*class="avatar"[^>]*>\s*JM\s*</span>#', $html));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userWithPhoto(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $path = UploadedFile::fake()->image('photo.jpg')->store('profile-photos', 'public');
        $user->forceFill(['profile_photo_path' => $path])->save();

        return $user;
    }
}
