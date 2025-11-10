<?php

namespace Monstrex\Ave\Admin\Resources\Role;

use Monstrex\Ave\Admin\Models\Role as RoleModel;
use Monstrex\Ave\Core\Columns\Column;
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
            TextInput::make('name')
                ->label('Role Name')
                ->required()
                ->maxLength(150),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(150)
                ->help('Unique identifier, e.g. admin, editor'),
            Textarea::make('description')
                ->label('Description')
                ->rows(3),
            Toggle::make('is_default')
                ->label('Default role')
                ->help('Automatically assigned to new users'),
        ]);
    }
}
