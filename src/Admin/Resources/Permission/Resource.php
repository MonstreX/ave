<?php

namespace Monstrex\Ave\Admin\Resources\Permission;

use Monstrex\Ave\Models\Permission as PermissionModel;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Components\Div;
use Monstrex\Ave\Core\Fields\Textarea;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource as BaseResource;
use Monstrex\Ave\Core\Table;

class Resource extends BaseResource
{
    public static ?string $model = PermissionModel::class;
    public static ?string $label = null;
    public static ?string $singularLabel = null;
    public static ?string $icon = 'voyager-key';
    public static ?string $slug = 'permissions';
    public static ?string $group = null;

    public static function getLabel(): string
    {
        return static::$label ?? __('ave::resources_permissions.label');
    }

    public static function getSingularLabel(): string
    {
        return static::$singularLabel ?? __('ave::resources_permissions.singular');
    }

    public static function getGroup(): ?string
    {
        return static::$group ?? __('ave::resources_groups.system');
    }

    public static function table($context): Table
    {
        return Table::make()->columns([
            Column::make('resource_slug')
                ->label(__('ave::resources_permissions.columns.resource'))
                ->sortable(true)
                ->linkAction('edit')
                ->searchable(true),
            Column::make('ability')
                ->label(__('ave::resources_permissions.columns.ability'))
                ->sortable(true)
                ->searchable(true),
            Column::make('name')
                ->label(__('ave::resources_permissions.columns.name')),
            Column::make('description')
                ->label(__('ave::resources_permissions.columns.description')),
            Column::make('created_at')
                ->label(__('ave::resources_permissions.columns.created_at'))
                ->format(fn ($value) => optional($value)?->format('Y-m-d H:i')),
        ]);
    }

    public static function form($context): Form
    {
        return Form::make()->schema([
            Div::make('row')->schema([
                Div::make('col-12 col-md-6')->schema([
                    TextInput::make('resource_slug')
                        ->label(__('ave::resources_permissions.fields.resource_slug'))
                        ->required(),
                ]),
                Div::make('col-12 col-md-6')->schema([
                    TextInput::make('ability')
                        ->label(__('ave::resources_permissions.fields.ability'))
                        ->required(),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12')->schema([
                    TextInput::make('name')
                        ->label(__('ave::resources_permissions.fields.name')),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12')->schema([
                    Textarea::make('description')
                        ->label(__('ave::resources_permissions.fields.description'))
                        ->rows(3),
                ]),
            ]),
        ]);
    }
}
