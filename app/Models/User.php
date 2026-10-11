<?php

namespace App\Models;

use App\Enums\Role;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property-read string $name
 * @property string $email
 * @property string|null $phone
 * @property string $locale
 * @property Role $role
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, SchoolClass> $schoolClasses
 * @property-read Collection<int, Stand> $managedStands
 * @property-read Collection<int, Registration> $registrations
 */
#[Fillable(['first_name', 'last_name', 'email', 'phone', 'locale', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'role' => Role::class,
        ];
    }

    /**
     * Full name, first name first.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    /**
     * @return BelongsToMany<SchoolClass, $this>
     */
    public function schoolClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class)->orderBy('position');
    }

    /**
     * @return BelongsToMany<Stand, $this>
     */
    public function managedStands(): BelongsToMany
    {
        return $this->belongsToMany(Stand::class, 'stand_manager');
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * @return HasMany<Commitment, $this>
     */
    public function commitments(): HasMany
    {
        return $this->hasMany(Commitment::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * First name and last name initial, e.g. "Camille M.".
     */
    public function shortName(): string
    {
        return trim($this->first_name.' '.Str::upper(Str::substr($this->last_name, 0, 1)).'.');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::upper(Str::substr($this->first_name, 0, 1).Str::substr($this->last_name, 0, 1));
    }
}
