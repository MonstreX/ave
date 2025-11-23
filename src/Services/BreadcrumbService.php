<?php

namespace Monstrex\Ave\Services;

use Illuminate\Support\Collection;

class BreadcrumbService
{
    /**
     * Array of custom breadcrumb resolvers
     * Format: 'route-pattern' => callable that returns breadcrumbs array
     */
    protected array $resolvers = [];

    /**
     * Register a custom breadcrumb resolver for a specific route pattern
     *
     * @param string $pattern Route pattern or path segment (e.g., 'ave-site.settings.edit', 'site-settings')
     * @param callable $resolver Function that receives request and returns breadcrumb items
     * @return $this
     */
    public function register(string $pattern, callable $resolver): static
    {
        $this->resolvers[$pattern] = $resolver;
        return $this;
    }

    /**
     * Get breadcrumbs for current request
     *
     * @return Collection Array of breadcrumb items
     */
    public function generate(): Collection
    {
        $dashboardRoute = \Illuminate\Support\Facades\Route::has('ave.dashboard')
            ? route('ave.dashboard')
            : null;

        $breadcrumbs = collect();

        // Add dashboard link
        if ($dashboardRoute) {
            $breadcrumbs->push([
                'url' => $dashboardRoute,
                'label' => __('ave::dashboard.title'),
                'icon' => 'voyager-boat',
            ]);
        }

        // Check for custom resolver
        $currentPath = trim(parse_url(request()->url(), PHP_URL_PATH), '/');
        $currentRouteName = request()->route()?->getName();

        // Try to find resolver by route name
        if ($currentRouteName && isset($this->resolvers[$currentRouteName])) {
            $customBreadcrumbs = ($this->resolvers[$currentRouteName])(request());
            if ($customBreadcrumbs) {
                return $breadcrumbs->merge($customBreadcrumbs);
            }
        }

        // Try to find resolver by path segment
        foreach ($this->resolvers as $pattern => $resolver) {
            if (strpos($currentPath, $pattern) !== false) {
                $customBreadcrumbs = $resolver(request());
                if ($customBreadcrumbs) {
                    return $breadcrumbs->merge($customBreadcrumbs);
                }
            }
        }

        // Generate default breadcrumbs from URL segments
        if ($dashboardRoute) {
            $segments = $this->extractSegments($currentPath, $dashboardRoute);
            $breadcrumbs = $breadcrumbs->merge($this->generateFromSegments($segments, $dashboardRoute));
        }

        return $breadcrumbs;
    }

    /**
     * Extract URL segments for breadcrumb generation
     */
    protected function extractSegments(string $currentPath, string $dashboardRoute): array
    {
        $dashboardPath = trim(parse_url($dashboardRoute, PHP_URL_PATH), '/');

        if ($dashboardPath !== '' && str_starts_with($currentPath, $dashboardPath)) {
            $relative = trim(substr($currentPath, strlen($dashboardPath)), '/');
        } else {
            $relative = $currentPath;
        }

        $segments = $relative === '' ? [] : explode('/', $relative);
        $segments = array_values(array_filter($segments, fn($segment) => $segment !== ''));

        // Remove 'edit' and 'create' from end
        if (!empty($segments)) {
            $last = end($segments);
            if (in_array($last, ['edit', 'create'], true)) {
                array_pop($segments);
            }
        }

        return $segments;
    }

    /**
     * Generate breadcrumbs from URL segments
     */
    protected function generateFromSegments(array $segments, string $dashboardRoute): Collection
    {
        $breadcrumbs = collect();
        $skipSegments = ['resource', 'page'];
        $visibleSegments = array_values(array_filter(
            $segments,
            fn ($segment) => ! in_array($segment, $skipSegments, true)
        ));
        $visibleCount = count($visibleSegments);

        $url = $dashboardRoute;
        $visibleIndex = 0;

        foreach ($segments as $segment) {
            $url .= '/' . $segment;

            if (in_array($segment, $skipSegments, true)) {
                continue;
            }

            $label = ucfirst(urldecode($segment));
            $isLastVisible = ($visibleIndex === $visibleCount - 1);
            $visibleIndex++;

            $breadcrumbs->push([
                'url' => $isLastVisible ? null : $url,
                'label' => $label,
                'icon' => null,
                'active' => $isLastVisible,
            ]);
        }

        return $breadcrumbs;
    }
}
