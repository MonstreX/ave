<?php

namespace Monstrex\Ave\Core\Rendering;

use Monstrex\Ave\Core\Resource;
use Illuminate\Pagination\Paginator;

class ResourceRenderer
{
    /**
     * Render resource index view
     *
     * @param Resource $resource
     * @param mixed $records Paginated records collection
     * @param array $options Additional options
     * @return string Rendered HTML
     */
    public function render(Resource $resource, $records, array $options = []): string
    {
        $data = [
            'resourceClass' => $resource::class,
            'routeBaseName' => 'ave.resources.' . $resource->slug(),
            'table' => $resource->table(),
            'data' => $records,
            'metrics' => $options['metrics'] ?? [],
            'queryTags' => $options['queryTags'] ?? [],
            'handlers' => $options['handlers'] ?? [],
        ];

        return view('ave::resources.index', $data)->render();
    }

    /**
     * Render resource create view
     *
     * @param Resource $resource
     * @param array $options
     * @return string
     */
    public function renderCreate(Resource $resource, array $options = []): string
    {
        $data = [
            'resourceClass' => $resource::class,
            'routeBaseName' => 'ave.resources.' . $resource->slug(),
            'form' => $resource->form(),
        ];

        return view('ave::resources.create', $data)->render();
    }

    /**
     * Render resource edit view
     *
     * @param Resource $resource
     * @param $model
     * @param array $options
     * @return string
     */
    public function renderEdit(Resource $resource, $model, array $options = []): string
    {
        $data = [
            'resourceClass' => $resource::class,
            'routeBaseName' => 'ave.resources.' . $resource->slug(),
            'form' => $resource->form(),
            'model' => $model,
        ];

        return view('ave::resources.edit', $data)->render();
    }
}
