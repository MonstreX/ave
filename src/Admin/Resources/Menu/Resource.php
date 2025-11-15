<?php

namespace Monstrex\Ave\Admin\Resources\Menu;

use Monstrex\Ave\Models\Menu as MenuModel;
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
    public static ?string $label = null;
    public static ?string $singularLabel = null;
    public static ?string $icon = 'voyager-list';
    public static ?string $slug = 'menus';
    public static ?string $group = null;

    public static function getLabel(): string
    {
        return static::$label ?? __('ave::resources.menus.label');
    }

    public static function getSingularLabel(): string
    {
        return static::$singularLabel ?? __('ave::resources.menus.singular');
    }

    public static function getGroup(): ?string
    {
        return static::$group ?? __('ave::resources.groups.system');
    }

    public static function actions(): array
    {
        return [
            MenuBuilderAction::class,
        ];
    }

    public static function table($context): Table
    {
        return Table::make()->columns([
            Column::make('name')
                ->label(__('ave::resources.menus.columns.name'))
                ->sortable(true),
            Column::make('slug')
                ->label(__('ave::resources.menus.columns.slug'))
                ->sortable(true),
            Column::make('is_default')
                ->label(__('ave::resources.menus.columns.is_default'))
                ->format(fn ($value) => $value ? __('ave::common.yes') : __('ave::common.no')),
            Column::make('created_at')
                ->label(__('ave::resources.menus.columns.created_at'))
                ->format(fn ($value) => optional($value)?->format('Y-m-d H:i')),
        ]);
    }

    public static function form($context): Form
    {
        return Form::make()->schema([
            Div::make('row')->schema([
                Div::make('col-12 col-md-6')->schema([
                    TextInput::make('name')
                        ->label(__('ave::resources.menus.fields.name'))
                        ->required(),
                ]),
                Div::make('col-12 col-md-6')->schema([
                    TextInput::make('slug')
                        ->label(__('ave::resources.menus.fields.slug'))
                        ->required(),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-md-4')->schema([
                    Toggle::make('is_default')
                        ->label(__('ave::resources.menus.fields.is_default')),
                ]),
            ]),
        ]);
    }
}
