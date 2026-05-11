<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'password', 'role', 'access_all_branches'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_MANAGER = 'manager';

    public const ROLE_SUPERVISOR = 'supervisor';

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function roleLabel(): string
    {
        return $this->role === self::ROLE_MANAGER ? 'مدير' : 'مشرف';
    }

    public function canAccessBranch(int|Branch $branch): bool
    {
        if ($this->access_all_branches) {
            return true;
        }

        $branchId = $branch instanceof Branch ? $branch->getKey() : $branch;

        return $this->branches()->whereKey($branchId)->exists();
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'access_all_branches' => 'boolean',
        ];
    }
}
