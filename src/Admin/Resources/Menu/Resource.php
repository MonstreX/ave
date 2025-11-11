<?php

namespace Monstrex\Ave\Admin\Resources\Menu;

use Monstrex\Ave\Admin\Models\Menu as MenuModel;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Components\Div;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Toggle;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource as BaseResource;
use Monstrex\Ave\Core\Table;

class Resource extends BaseResource
{
    public static ?string $model = MenuModel::class;
    public static ?string $label = 'Menus';
    public static ?string $singularLabel = 'Menu';
    public static ?string $icon = 'voyager-list';
    public static ?string $slug = 'menus';
    public static ?string $group = 'System';

    public static function table($context): Table
    {
        return Table::make()->columns([
            Column::make('name')
                ->label('Name')
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
                        ->label('Menu name')
                        ->required(),
                ]),
                Div::make('col-12 col-md-6')->schema([
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required(),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-md-4')->schema([
                    Toggle::make('is_default')
                        ->label('Default menu'),
                ]),
            ]),
        ]);
    }
}
