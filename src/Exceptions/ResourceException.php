<?php

namespace Monstrex\Ave\Exceptions;

use Exception;

class ResourceException extends Exception
{
    public static function notFound(string $slug): self
    {
        return new self("Resource '{$slug}' not found", 404);
    }

    public static function modelNotFound(string $slug, mixed $id): self
    {
        return new self("Model '{$id}' not found for resource '{$slug}'", 404);
    }

    public static function unauthorized(string $slug, string $ability): self
    {
        return new self("Unauthorized to '{$ability}' on resource '{$slug}'", 403);
    }

    public static function invalidModel(string $resourceClass): self
    {
        return new self("Resource '{$resourceClass}' has no valid model class", 500);
    }
}
