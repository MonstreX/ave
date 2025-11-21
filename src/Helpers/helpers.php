<?php

if (! function_exists('humanFileSize')) {
    /**
     * Convert bytes to human-readable format
     *
     * Removes trailing zeros after decimal point (e.g., "40KB" not "40.0KB")
     */
    function humanFileSize(int $bytes, int $decimals = 1): string
    {
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        if ($bytes <= 0) {
            return '0B';
        }
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        $value = $bytes / pow(1024, $factor);
        $formatted = sprintf("%.{$decimals}f", $value);

        // Remove trailing zeros and decimal point if not needed
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted . $sizes[$factor];
    }
}

if (! function_exists('ave_auth_guard')) {
    function ave_auth_guard(): ?string
    {
        $guard = trim((string) config('ave.auth_guard'));
        return $guard !== '' ? $guard : null;
    }
}

if (! function_exists('ave_auth_user')) {
    function ave_auth_user()
    {
        $guard = ave_auth_guard();

        return $guard ? auth($guard)->user() : auth()->user();
    }
}

if (! function_exists('ave_auth_check')) {
    function ave_auth_check(): bool
    {
        $guard = ave_auth_guard();

        return $guard ? auth($guard)->check() : auth()->check();
    }
}

if (! function_exists('ave_login_route_name')) {
    function ave_login_route_name(): string
    {
        return config('ave.login_route', 'login');
    }
}

if (! function_exists('ave_login_submit_route_name')) {
    function ave_login_submit_route_name(): string
    {
        return config('ave.login_submit_route', 'login.submit');
    }
}

if (!function_exists('ave_js_translations')) {
    /**
     * Get JavaScript translations for the current locale
     *
     * @return array
     */
    function ave_js_translations(): array
    {
        $locale = app()->getLocale();

        return __('ave::js', [], $locale);
    }
}

if (!function_exists('ave_js_translations_json')) {
    /**
     * Get JavaScript translations as JSON string
     *
     * @return string
     */
    function ave_js_translations_json(): string
    {
        return json_encode(ave_js_translations(), JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('menu')) {
    /**
     * Display a menu by name with optional custom template
     *
     * @param string $menuName Menu name or slug
     * @param string|null $template Custom blade template path (e.g., 'partials.menus.main')
     * @param array $options Additional options passed to the view
     * @return \Illuminate\Support\HtmlString|string
     */
    function menu(string $menuName, ?string $template = null, array $options = [])
    {
        $menu = \Monstrex\Ave\Models\Menu::with(['items' => function ($query) {
            $query->whereNull('parent_id')->orderBy('order');
        }, 'items.children' => function ($query) {
            $query->orderBy('order');
        }])->where('name', $menuName)->orWhere('key', $menuName)->first();

        if (!$menu) {
            return '';
        }

        $items = $menu->items;

        // Use custom template or default
        $view = $template ?? 'ave::menu.default';

        if (!view()->exists($view)) {
            $view = 'ave::menu.default';
        }

        $options = (object) $options;

        return new \Illuminate\Support\HtmlString(
            view($view, ['items' => $items, 'options' => $options])->render()
        );
    }
}
