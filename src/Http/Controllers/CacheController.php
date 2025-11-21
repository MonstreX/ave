<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Monstrex\Ave\Services\CacheService;

class CacheController extends Controller
{
    public function __construct(
        private CacheService $cacheService
    ) {
    }

    /**
     * Clear specific cache type.
     */
    public function clear(string $type): JsonResponse
    {
        $result = $this->cacheService->clear($type);

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
