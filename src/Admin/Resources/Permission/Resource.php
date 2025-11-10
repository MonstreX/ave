<?php

namespace Monstrex\Ave\Admin\Resources\Permission;

use Monstrex\Ave\Admin\Models\Permission as PermissionModel;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Fields\Textarea;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource as BaseResource;
use Monstrex\Ave\Core\Table;

class Resource extends BaseResource
{
    public static ?string $model = PermissionModel::class;
    public static ?string $label = 'Permissions';
    public static ?string $singularLabel = 'Permission';
    public static ?string $icon = 'voyager-key';
    public static ?string $slug = 'permissions';
    public static ?string $group = 'System';

    public static function table($context): Table
    {
        return Table::make()->columns([
            Column::make('resource_slug')
                ->label('Resource')
                ->sortable(true)
                ->searchable(true),
            Column::make('ability')
                ->label('Ability')
                ->sortable(true)
                ->searchable(true),
            Column::make('name')
                ->label('Name'),
            Column::make('description')
                ->label('Description'),
            Column::make('created_at')
                ->label('Created')
                ->format(fn ($value) => optional($value)?->format('Y-m-d H:i')),
        ]);
    }

    public static function form($context): Form
    {
        return Form::make()->schema([
            TextInput::make('resource_slug')
                ->label('Resource slug')
                ->required(),
            TextInput::make('ability')
                ->label('Ability')
                ->required(),
            TextInput::make('name')
                ->label('Display name'),
            Textarea::make('description')
                ->label('Description')
                ->rows(3),
        ]);
    }
}
