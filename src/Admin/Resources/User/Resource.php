<?php

namespace Monstrex\Ave\Admin\Resources\User;

use Monstrex\Ave\Admin\Models\User as UserModel;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Fields\BelongsToManySelect;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource as BaseResource;
use Monstrex\Ave\Core\Table;

class Resource extends BaseResource
{
    public static ?string $model = UserModel::class;
    public static ?string $label = 'Users';
    public static ?string $singularLabel = 'User';
    public static ?string $icon = 'voyager-person';
    public static ?string $slug = 'users';
    public static ?string $group = 'System';

    public static function table($context): Table
    {
        return Table::make()->columns([
            Column::make('id')
                ->label('ID')
                ->sortable(true),
            Column::make('name')
                ->label('Name')
                ->sortable(true)
                ->searchable(true),
            Column::make('email')
                ->label('Email')
                ->searchable(true),
            Column::make('roles')
                ->label('Roles')
                ->format(fn ($value, $record) => $record->roles->pluck('name')->implode(', ')),
        ]);
    }

    public static function form($context): Form
    {
        return Form::make()->schema([
            TextInput::make('name')
                ->label('Name')
                ->disabled()
                ->placeholder('Managed via application'),
            TextInput::make('email')
                ->label('Email')
                ->disabled()
                ->placeholder('Managed via application'),
            BelongsToManySelect::make('roles')
                ->label('Roles')
                ->relationship('roles', 'name')
                ->searchable()
                ->optionsLimit(100)
                ->help('Assign one or more roles to this user'),
        ]);
    }
}
