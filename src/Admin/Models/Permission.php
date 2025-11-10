<?php

namespace Monstrex\Ave\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'ave_permissions';

    protected $fillable = [
        'resource_slug',
        'ability',
        'name',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'ave_permission_role')
            ->withTimestamps();
    }
}
