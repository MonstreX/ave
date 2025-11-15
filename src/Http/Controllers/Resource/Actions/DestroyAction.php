<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Exceptions\ResourceException;

class DestroyAction extends AbstractResourceAction
{
    public function __construct(
        ResourceManager $resources,
        protected ResourcePersistence $persistence
    )
    {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug, string $id)
    {
        $resourceClass = $this->resolveResourceClass($slug);

        $model = $this->findModelOrFail($resourceClass, $slug, $id);
        $this->resolveAndAuthorize($slug, 'delete', $request, $model);
        $this->persistence->delete($resourceClass, $model, $request);

        return redirect()->route('ave.resource.index', ['slug' => $slug])
            ->with('status', __('Deleted successfully'));
    }
}
