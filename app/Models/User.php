<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

// "role" is deliberately not fillable: only UserController sets it, after its checks.
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const STORE_MANAGER = 'store_manager';

    public const ADMIN = 'admin';

    public const SELLER = 'seller';

    /**
     * Roles and their labels, from most to least access.
     */
    public const ROLES = [
        self::STORE_MANAGER => 'Store Manager',
        self::ADMIN => 'Admin',
        self::SELLER => 'Seller',
    ];

    /**
     * Which roles may open each section of the panel. Each entry becomes a
     * gate named "access-<section>" (see AppServiceProvider), used by the routes,
     * the navigation and the pages. My Profile, search and notifications are
     * open to every signed-in user and are not listed.
     */
    public const SECTION_ROLES = [
        'dashboard' => [self::STORE_MANAGER, self::ADMIN],
        'products' => [self::STORE_MANAGER, self::ADMIN, self::SELLER],
        'categories' => [self::STORE_MANAGER, self::ADMIN],
        'orders' => [self::STORE_MANAGER, self::ADMIN, self::SELLER],
        'customers' => [self::STORE_MANAGER, self::ADMIN],
        'messages' => [self::STORE_MANAGER, self::ADMIN, self::SELLER],
        'settings' => [self::STORE_MANAGER],
        'users' => [self::STORE_MANAGER],
    ];

    public function canAccessSection(string $section): bool
    {
        return in_array($this->role, self::SECTION_ROLES[$section] ?? [], true);
    }

    public function isStoreManager(): bool
    {
        return $this->role === self::STORE_MANAGER;
    }

    /**
     * True for the only Store Manager left: the panel must always keep one.
     */
    public function isLastStoreManager(): bool
    {
        return $this->isStoreManager() && static::where('role', self::STORE_MANAGER)->count() === 1;
    }

    /**
     * Where this user starts: the dashboard, or the products list for roles without it.
     */
    public function homeUrl(): string
    {
        return $this->canAccessSection('dashboard') ? route('dashboard') : route('products.index');
    }

    /**
     * The role's name for display, e.g. "Store Manager".
     */
    protected function roleLabel(): Attribute
    {
        return Attribute::get(fn () => self::ROLES[$this->role] ?? ucfirst((string) $this->role));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Not fillable: only NotificationController changes it.
            'notifications_read_at' => 'datetime',
        ];
    }

    /**
     * Public address of the profile photo, or null without one. The path is not
     * fillable: only ProfileController sets it, to a file it stored itself.
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->profile_photo_path
            ? Storage::disk('public')->url($this->profile_photo_path)
            : null);
    }
}
