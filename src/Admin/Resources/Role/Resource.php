<?php

namespace Monstrex\Ave\Admin\Resources\Role;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Monstrex\Ave\Admin\Components\PermissionMatrix;
use Monstrex\Ave\Admin\Models\Role as RoleModel;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Components\Div;
use Monstrex\Ave\Core\Fields\Textarea;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Toggle;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource as BaseResource;
use Monstrex\Ave\Core\Table;

class Resource extends BaseResource
{
    public static ?string $model = RoleModel::class;
    public static ?string $label = 'Roles';
    public static ?string $singularLabel = 'Role';
    public static ?string $icon = 'voyager-lock';
    public static ?string $slug = 'roles';
    public static ?string $group = 'System';

    public static function table($context): Table
    {
        return Table::make()->columns([
            Column::make('name')
                ->label('Name')
                ->searchable(true)
                ->sortable(true),
            Column::make('slug')
                ->label('Slug')
                ->sortable(true),
            Column::make('is_default')
                ->label('Default')
                ->format(fn ($value) => $value ? 'Yes' : 'No'),
            Column::make('created_at')
                ->label('Created')
                ->format(fn ($value) => optional($value)?->format('Y-m-d H:i')),
        ]);
    }

    public static function form($context): Form
    {
        return Form::make()->schema([
            Div::make('row')->schema([
                Div::make('col-12 col-md-6')->schema([
                    TextInput::make('name')
                        ->label('Role Name')
                        ->required()
                        ->maxLength(150),
                ]),
                Div::make('col-12 col-md-6')->schema([
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(150)
                        ->help('Unique identifier, e.g. admin, editor'),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12')->schema([
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-md-4')->schema([
                    Toggle::make('is_default')
                        ->label('Default role')
                        ->help('Automatically assigned to new users'),
                ]),
            ]),
            PermissionMatrix::make()->label('Permissions'),
        ]);
    }

    public static function syncRelations(Model $model, array $data, Request $request): void
    {
        if (! method_exists($model, 'permissions') || ! Schema::hasTable('ave_permission_role')) {
            return;
        }

        $permissions = $request->input('permissions', []);

        if (! is_array($permissions)) {
            $permissions = [];
        }

        $model->permissions()->sync(static::normalizePermissionInput($permissions));
    }

    /**
     * @param  array<int|string,mixed>  $input
     * @return array<int,int>
     */
    protected static function normalizePermissionInput(array $input): array
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
}
