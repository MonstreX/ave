<?php

namespace Monstrex\Ave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $table = 'ave_menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'icon',
        'route',
        'url',
        'target',
        'order',
        'permission_key',
        'resource_slug',
        'ability',
        'is_divider',
    ];

    protected $casts = [
        'is_divider' => 'bool',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('order');
    }

    public function getLink(): string
    {
        // If custom URL is set, use it
        if ($this->url) {
            return $this->url;
        }

        // If route is set, generate URL from route
        if ($this->route) {
            try {
                return route($this->route);
            } catch (\Exception $e) {
                return '#';
            }
        }

        // Default to hash
        return '#';
    }
}
