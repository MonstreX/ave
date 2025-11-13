<?php

namespace Monstrex\Ave\Admin\Resources\MenuItem;

use Monstrex\Ave\Models\MenuItem as MenuItemModel;
use Monstrex\Ave\Models\Menu as MenuModel;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Components\Div;
use Monstrex\Ave\Core\Fields\BelongsToSelect;
use Monstrex\Ave\Core\Fields\Number;
use Monstrex\Ave\Core\Fields\Select;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Toggle;
use Monstrex\Ave\Core\Filters\SelectFilter;
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
        return Table::make()
            ->tree(
                parentColumn: 'parent_id',
                orderColumn: 'order',
                labelColumn: 'title',
                maxDepth: 5
            )
            ->columns([
                Column::make('icon')
                    ->label('Icon')
                    ->format(fn ($value) => $value ? "<i class='{$value}'></i>" : ''),
                Column::make('resource_slug')
                    ->label('Resource'),
                Column::make('route')
                    ->label('Route'),
                Column::make('url')
                    ->label('URL'),
            ])
            ->filters([
                SelectFilter::make('menu_id')
                    ->label('Menu')
                    ->options(MenuModel::pluck('name', 'id')->toArray())
                    ->default(MenuModel::where('slug', 'main')->value('id')),
            ])
            ->searchable(false); // Disable search in tree mode
    }

    public static function form($context): Form
    {
        return Form::make()->schema([
            Div::make('row')->schema([
                Div::make('col-12 col-lg-6')->schema([
                    BelongsToSelect::make('menu_id')
                        ->label('Menu')
                        ->relationship('menu', 'name')
                        ->required(),
                ]),
                Div::make('col-12 col-lg-6')->schema([
                    BelongsToSelect::make('parent_id')
                        ->label('Parent item')
                        ->relationship('parent', 'title')
                        ->nullable(),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-lg-6')->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required(),
                ]),
                Div::make('col-12 col-lg-6')->schema([
                    TextInput::make('icon')
                        ->label('Icon class')
                        ->placeholder('voyager-dot'),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-lg-6')->schema([
                    TextInput::make('route')
                        ->label('Route name')
                        ->help('Laravel route name, e.g. ave.resource.index'),
                ]),
                Div::make('col-12 col-lg-6')->schema([
                    TextInput::make('url')
                        ->label('Custom URL')
                        ->help('Overrides route if provided'),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-lg-4')->schema([
                    TextInput::make('resource_slug')
                        ->label('Resource slug')
                        ->help('Automatically links to resource index'),
                ]),
                Div::make('col-12 col-lg-4')->schema([
                    TextInput::make('ability')
                        ->label('Ability')
                        ->default('viewAny')
                        ->help('Used with resource slug'),
                ]),
                Div::make('col-12 col-lg-4')->schema([
                    TextInput::make('permission_key')
                        ->label('Permission key')
                        ->placeholder('resource.ability'),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-lg-6')->schema([
                    Select::make('target')
                        ->label('Target')
                        ->options([
                            '_self' => 'Same tab',
                            '_blank' => 'New tab',
                        ])
                        ->default('_self'),
                ]),
                Div::make('col-12 col-lg-6')->schema([
                    Number::make('order')
                        ->label('Order')
                        ->default(0),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-md-4')->schema([
                    Toggle::make('is_divider')
                        ->label('Divider'),
                ]),
            ]),
        ]);
    }
}
