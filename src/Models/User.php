<?php

namespace Monstrex\Ave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
