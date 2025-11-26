<?php

namespace Monstrex\Ave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $table = 'ave_menus';

    protected $fillable = [
        'name',
        'key',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'bool',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->orderBy('order');
    }
}
