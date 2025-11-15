<?php

namespace Monstrex\Ave\Admin\Resources\User;

use Monstrex\Ave\Models\User as UserModel;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Components\Div;
use Monstrex\Ave\Core\Fields\BelongsToManySelect;
use Monstrex\Ave\Core\Fields\PasswordInput;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource as BaseResource;
use Monstrex\Ave\Core\Table;

class Resource extends BaseResource
{
    public static ?string $model = UserModel::class;
    public static ?string $label = null;
    public static ?string $singularLabel = null;
    public static ?string $icon = 'voyager-person';
    public static ?string $slug = 'users';
    public static ?string $group = null;

    public static function getLabel(): string
    {
        return static::$label ?? __('ave::resources_users.label');
    }

    public static function getSingularLabel(): string
    {
        return static::$singularLabel ?? __('ave::resources_users.singular');
    }

    public static function getGroup(): ?string
    {
        return static::$group ?? __('ave::resources_groups.system');
    }

    public static function table($context): Table
    {
        return Table::make()->columns([
            Column::make('id')
                ->label(__('ave::resources_users.columns.id'))
                ->sortable(true),
            Column::make('name')
                ->label(__('ave::resources_users.columns.name'))
                ->sortable(true)
                ->searchable(true),
            Column::make('email')
                ->label(__('ave::resources_users.columns.email'))
                ->searchable(true),
            Column::make('roles')
                ->label(__('ave::resources_users.columns.roles'))
                ->format(fn ($value, $record) => $record->roles->pluck('name')->implode(', ')),
        ]);
    }

    public static function form($context): Form
    {
        return Form::make()->schema([
            Div::make('row')->schema([
                Div::make('col-12 col-md-6')->schema([
                    TextInput::make('name')
                        ->label(__('ave::resources_users.fields.name'))
                        ->required()
                        ->rules(['required', 'string', 'max:255']),
                ]),
                Div::make('col-12 col-md-6')->schema([
                    TextInput::make('email')
                        ->label(__('ave::resources_users.fields.email'))
                        ->email()
                        ->required()
                        ->rules(['required', 'email', 'max:255']),
                ]),
            ]),
            Div::make('row')->schema([
                Div::make('col-12 col-md-6')->schema([
                    PasswordInput::make('password')
                        ->label(__('ave::resources_users.fields.password'))
                        ->minLength(8)
                        ->rules(['nullable', 'string', 'min:8'])
                        ->help(__('ave::resources_users.help.password')),
                ]),
                Div::make('col-12')->schema([
                    BelongsToManySelect::make('roles')
                        ->label(__('ave::resources_users.fields.roles'))
                        ->relationship('roles', 'name')
                        ->searchable()
                        ->optionsLimit(100)
                        ->help(__('ave::resources_users.help.roles')),
                ]),
            ]),
        ]);
    }
}
