<?php

namespace Monstrex\Ave\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Monstrex\Ave\Services\SlugService;

/**
 * SlugController - API endpoint for slug generation.
 *
 * Provides AJAX endpoint for client-side slug field to request
 * server-side slug generation. This ensures a single source of truth
 * for slug generation logic and transliteration.
 *
 * Endpoint: POST /admin/ave/api/slug
 *
 * Example Request:
 *   {
 *     "text": "Привет мир",
 *     "separator": "-",
 *     "locale": "ru"
 *   }
 *
 * Example Response:
 *   {
 *     "slug": "privet-mir"
 *   }
 */
class SlugController extends Controller
{
    /**
     * Generate a slug from provided text.
     *
     * Validates input and uses SlugService to generate URL-friendly slug.
     * Supports multiple languages and custom separators.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:500',
            'separator' => 'nullable|string|max:5',
            'locale' => 'nullable|string|max:5',
        ]);

        $text = $validated['text'];
        $separator = $validated['separator'] ?? '-';
        $locale = $validated['locale'] ?? null;

        $slug = SlugService::make($text, $separator, $locale);

        return response()->json([
            'slug' => $slug,
        ]);
    }
}
