<?php

namespace Monstrex\Ave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash;

class User extends Model
{
    protected $table;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        $this->table = config('ave.user_table', 'users');
        parent::__construct($attributes);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'ave_role_user')
            ->withTimestamps();
    }

    public function setPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            unset($this->attributes['password']);
            return;
        }

        $info = password_get_info($value);
        if (($info['algo'] ?? 0) !== 0) {
            $this->attributes['password'] = $value;
            return;
        }

        $this->attributes['password'] = Hash::make($value);
    }
}
