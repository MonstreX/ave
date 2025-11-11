<?php

namespace Monstrex\Ave\Admin\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Monstrex\Ave\Admin\Models\Permission;
use Monstrex\Ave\Core\Components\FormComponent;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\ResourceManager;

class PermissionMatrix extends FormComponent
{
    protected ?Collection $groups = null;

    /**
     * @var array<int,int>
     */
    protected array $selected = [];

    protected string $label = 'Permissions';

    public static function make(): static
    {
        return new static();
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    protected function ensureHydrated(FormContext $context): void
    {
        if ($this->groups !== null) {
            return;
        }

        if (! Schema::hasTable('ave_permissions')) {
            $this->groups = collect();
            $this->selected = $this->resolveSelectedPermissions($context);

            return;
        }

        $permissions = Permission::query()
            ->orderBy('resource_slug')
            ->orderBy('ability')
            ->get();

        $resourceLabels = $this->resolveResourceLabels(
            $permissions->pluck('resource_slug')->unique()->all()
        );

        $this->groups = $permissions
            ->groupBy('resource_slug')
            ->map(function ($group, string $slug) use ($resourceLabels) {
                return [
                    'slug' => $slug,
                    'label' => $resourceLabels[$slug] ?? $this->humanizeSlug($slug),
                    'permissions' => $group->map(function (Permission $permission) {
                        return [
                            'id' => $permission->id,
                            'ability' => $permission->ability,
                            'label' => $permission->name ?: $this->humanizeSlug($permission->ability),
                            'description' => $permission->description,
                        ];
                    })->values()->all(),
                ];
            })
            ->values();

        $this->selected = $this->resolveSelectedPermissions($context);
    }

    protected function humanizeSlug(string $value): string
    {
        return Str::headline(str_replace(['-', '_'], ' ', $value));
    }

    /**
     * @param  array<int,string>  $slugs
     * @return array<string,string>
     */
    protected function resolveResourceLabels(array $slugs): array
    {
        if (empty($slugs) || ! app()->bound(ResourceManager::class)) {
            return [];
        }

        $labels = [];
        $manager = app(ResourceManager::class);

        foreach ($slugs as $slug) {
            $resourceClass = $manager->resource($slug);

            if ($resourceClass && method_exists($resourceClass, 'getLabel')) {
                $labels[$slug] = $resourceClass::getLabel();
            }
        }

        return $labels;
    }

    /**
     * @return array<int,int>
     */
    protected function resolveSelectedPermissions(FormContext $context): array
    {
        if ($context->hasOldInput('permissions')) {
            $old = $context->oldInput('permissions');

            if (is_array($old)) {
                return $this->normalizeSelection($old);
            }
        }

        $record = $context->record();

        if (! $record instanceof Model || ! method_exists($record, 'permissions')) {
            return [];
        }

        if (! Schema::hasTable('ave_permission_role')) {
            return [];
        }

        if ($record->relationLoaded('permissions')) {
            return $record->permissions->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $record->permissions()->pluck('ave_permissions.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<int|string,mixed>  $input
     * @return array<int,int>
     */
    protected function normalizeSelection(array $input): array
    {
        if (array_is_list($input)) {
            $ids = array_filter(
                array_map(
                    static fn ($value) => is_numeric($value) ? (int) $value : null,
                    $input
                ),
                static fn ($value) => $value !== null
            );

            return array_values(array_unique($ids));
        }

        $ids = [];

        foreach ($input as $key => $value) {
            if (is_numeric($key)) {
                $ids[] = (int) $key;
                continue;
            }

            if (is_numeric($value)) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    public function prepareForDisplay(FormContext $context): void
    {
        $this->ensureHydrated($context);

        parent::prepareForDisplay($context);
    }

    protected function getDefaultViewTemplate(): string
    {
        return 'ave::components.forms.permission-matrix';
    }

    public function render(FormContext $context): string
    {
        $this->ensureHydrated($context);

        return view($this->getViewTemplate(), [
            'component' => $this,
            'label' => $this->label,
            'groups' => $this->groups ?? collect(),
            'selected' => $this->selected,
        ])->render();
    }
}
