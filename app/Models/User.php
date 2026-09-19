<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_USER = 'user';

    public const ROLE_LOGISTICS = 'logistics';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    /**
     * Every role allowed on the platform. Shared across all feature branches.
     *
     * @var list<string>
     */
    public const ROLES = [
        self::ROLE_USER,
        self::ROLE_LOGISTICS,
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * Note: `role` is fillable for seeders and factories. Never pass a
     * user-supplied role into create()/update() from a request payload.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sellerPayouts(): HasMany
    {
        return $this->hasMany(SellerPayout::class, 'seller_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isUser(): bool
    {
        return $this->hasRole(self::ROLE_USER);
    }

    public function isLogistics(): bool
    {
        return $this->hasRole(self::ROLE_LOGISTICS);
    }

    /**
     * True for admins and super admins, since a super admin can do
     * everything an admin can do.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN) || $this->isSuperAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }
}
