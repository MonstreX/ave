<?php

namespace Monstrex\Ave\Admin\Resources\MenuItem;

use Monstrex\Ave\Models\MenuItem as MenuItemModel;
use Monstrex\Ave\Models\Menu as MenuModel;
use Monstrex\Ave\Core\Columns\BooleanColumn;
use Monstrex\Ave\Core\Columns\ComputedColumn;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Components\Div;
use Monstrex\Ave\Core\Criteria\FieldEqualsFilter;
use Monstrex\Ave\Core\Fields\Hidden;
use Monstrex\Ave\Core\Fields\Number;
use Monstrex\Ave\Core\Fields\Select;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Toggle;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource as BaseResource;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Actions\CreateInModalAction;
use Monstrex\Ave\Core\Actions\DeleteAction;
use Monstrex\Ave\Core\Actions\EditInModalAction;

class Resource extends BaseResource
{
    public static ?string $model = MenuItemModel::class;
    public static ?string $label = 'Menu Items';
    public static ?string $singularLabel = 'Menu Item';
    public static ?string $icon = 'voyager-list';
    public static ?string $slug = 'menu-items';
    public static ?string $group = 'System';

    public static function getCriteria(): array
    {
        return [
            new FieldEqualsFilter('menu_id', 'menu_id', '=', 'Menu'),
        ];
    }

    public static function beforeCreate(array $data, \Illuminate\Http\Request $request): array
    {
        return $data;
    }

    public static function getIndexRedirectParams(\Illuminate\Database\Eloquent\Model $model, \Illuminate\Http\Request $request, string $mode): array
    {
        // Redirect back to tree view with menu_id filter
        return isset($model->menu_id) ? ['menu_id' => $model->menu_id] : [];
    }

    public static function table($context): Table
    {
        return Table::make()
            ->tree(
                parentColumn: 'parent_id',
                orderColumn: 'order',
                maxDepth: 5
            )
            ->columns([
                BooleanColumn::make('status')
                    ->label('Active')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->trueValue(1)
                    ->falseValue(0)
                    ->trueIcon('voyager-check')
                    ->falseIcon('voyager-x')
                    ->inlineToggle(),
                Column::make('title')
                    ->bold(),
                ComputedColumn::make('target')
                    ->label('Target')
                    ->compute(function($record) {
                        // Priority: url > route > resource_slug
                        if (!empty($record->url)) {
                            return $record->url;
                        }
                        if (!empty($record->route)) {
                            return $record->route;
                        }
                        if (!empty($record->resource_slug)) {
                            return $record->resource_slug;
                        }
                        return null;
                    })
                    ->color('#3686e4')
                    ->fontSize('13px'),
            ])
            ->searchable(false); // Disable search in tree mode
    }

    public static function rowActions(): array
    {
        return [
            new EditInModalAction(), // Edit in modal popup
            new DeleteAction(),
        ];
    }

    public static function globalActions(): array
    {
        return [
            new CreateInModalAction(), // Create in modal popup
        ];
    }

    public static function form($context): Form
    {
        return Form::make()->schema([
            Div::make('')->schema([
                Hidden::make('menu_id'),
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
                    Toggle::make('status')
                        ->label('Active')
                        ->default(true),
                ]),
                Div::make('col-12 col-md-4')->schema([
                    Toggle::make('is_divider')
                        ->label('Divider'),
                ]),
            ]),
        ]);
    }
}
