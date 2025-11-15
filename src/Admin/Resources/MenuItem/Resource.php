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
    public static ?string $label = null;
    public static ?string $singularLabel = null;
    public static ?string $icon = 'voyager-list';
    public static ?string $slug = 'menu-items';
    public static ?string $group = null;

    public static function getLabel(): string
    {
        return static::$label ?? __('ave::resources.menu_items.label');
    }

    public static function getSingularLabel(): string
    {
        return static::$singularLabel ?? __('ave::resources.menu_items.singular');
    }

    public static function getGroup(): ?string
    {
        return static::$group ?? __('ave::resources.groups.system');
    }

    public static function getCriteria(): array
    {
        return [
            new FieldEqualsFilter('menu_id', 'menu_id', '=', __('ave::resources.menu_items.filters.menu')),
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
                    ->label(__('ave::resources.menu_items.columns.active'))
                    ->trueLabel(__('ave::common.active'))
                    ->falseLabel(__('ave::common.inactive'))
                    ->trueValue(1)
                    ->falseValue(0)
                    ->trueIcon('voyager-check')
                    ->falseIcon('voyager-x')
                    ->inlineToggle(),
                Column::make('title')
                    ->bold(),
                ComputedColumn::make('target')
                    ->label(__('ave::resources.menu_items.columns.target'))
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
                        ->label(__('ave::resources.menu_items.fields.title'))
                        ->required(),
                ]),
                Div::make('col-12 col-lg-6')->schema([
                    TextInput::make('icon')
                        ->label(__('ave::resources.menu_items.fields.icon'))
                        ->placeholder('voyager-dot'),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-lg-6')->schema([
                    TextInput::make('route')
                        ->label(__('ave::resources.menu_items.fields.route'))
                        ->help(__('ave::resources.menu_items.help.route')),
                ]),
                Div::make('col-12 col-lg-6')->schema([
                    TextInput::make('url')
                        ->label(__('ave::resources.menu_items.fields.url'))
                        ->help(__('ave::resources.menu_items.help.url')),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-lg-4')->schema([
                    TextInput::make('resource_slug')
                        ->label(__('ave::resources.menu_items.fields.resource_slug'))
                        ->help(__('ave::resources.menu_items.help.resource_slug')),
                ]),
                Div::make('col-12 col-lg-4')->schema([
                    TextInput::make('ability')
                        ->label(__('ave::resources.menu_items.fields.ability'))
                        ->default('viewAny')
                        ->help(__('ave::resources.menu_items.help.ability')),
                ]),
                Div::make('col-12 col-lg-4')->schema([
                    TextInput::make('permission_key')
                        ->label(__('ave::resources.menu_items.fields.permission_key'))
                        ->placeholder('resource.ability'),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-lg-6')->schema([
                    Select::make('target')
                        ->label(__('ave::resources.menu_items.fields.target'))
                        ->options([
                            '_self' => __('ave::resources.menu_items.options.same_tab'),
                            '_blank' => __('ave::resources.menu_items.options.new_tab'),
                        ])
                        ->default('_self'),
                ]),
                Div::make('col-12 col-lg-6')->schema([
                    Number::make('order')
                        ->label(__('ave::resources.menu_items.fields.order'))
                        ->default(0),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-md-4')->schema([
                    Toggle::make('status')
                        ->label(__('ave::resources.menu_items.fields.status'))
                        ->default(true),
                ]),
                Div::make('col-12 col-md-4')->schema([
                    Toggle::make('is_divider')
                        ->label(__('ave::resources.menu_items.fields.is_divider')),
                ]),
            ]),
        ]);
    }
}
