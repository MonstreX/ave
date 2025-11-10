<?php

namespace Monstrex\Ave\Admin\Resources\MenuItem;

use Monstrex\Ave\Admin\Models\MenuItem as MenuItemModel;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Fields\BelongsToSelect;
use Monstrex\Ave\Core\Fields\Number;
use Monstrex\Ave\Core\Fields\Select;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Toggle;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource as BaseResource;
use Monstrex\Ave\Core\Table;

class Resource extends BaseResource
{
    public static ?string $model = MenuItemModel::class;
    public static ?string $label = 'Menu Items';
    public static ?string $singularLabel = 'Menu Item';
    public static ?string $icon = 'voyager-list';
    public static ?string $slug = 'menu-items';
    public static ?string $group = 'System';

    public static function table($context): Table
    {
        return Table::make()->columns([
            Column::make('menu.name')
                ->label('Menu')
                ->sortable(true),
            Column::make('title')
                ->label('Title')
                ->sortable(true)
                ->searchable(true),
            Column::make('resource_slug')
                ->label('Resource'),
            Column::make('order')
                ->label('Order')
                ->sortable(true),
        ]);
    }

    public static function form($context): Form
    {
        return Form::make()->schema([
            BelongsToSelect::make('menu_id')
                ->label('Menu')
                ->relationship('menu', 'name')
                ->required(),
            BelongsToSelect::make('parent_id')
                ->label('Parent item')
                ->relationship('parent', 'title')
                ->nullable(),
            TextInput::make('title')
                ->label('Title')
                ->required(),
            TextInput::make('icon')
                ->label('Icon class')
                ->placeholder('voyager-dot'),
            TextInput::make('route')
                ->label('Route name')
                ->help('Laravel route name, e.g. ave.resource.index'),
            TextInput::make('url')
                ->label('Custom URL')
                ->help('Overrides route if provided'),
            TextInput::make('resource_slug')
                ->label('Resource slug')
                ->help('Automatically links to resource index'),
            TextInput::make('ability')
                ->label('Ability')
                ->default('viewAny')
                ->help('Used with resource slug'),
            TextInput::make('permission_key')
                ->label('Permission key')
                ->placeholder('resource.ability'),
            Select::make('target')
                ->label('Target')
                ->options([
                    '_self' => 'Same tab',
                    '_blank' => 'New tab',
                ])
                ->default('_self'),
            Number::make('order')
                ->label('Order')
                ->default(0),
            Toggle::make('is_divider')
                ->label('Divider'),
        ]);
    }
}
