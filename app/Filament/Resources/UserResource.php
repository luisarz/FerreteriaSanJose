<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;
use Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $label = 'Usuarios';
    protected static ?string $navigationGroup = 'Seguridad';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informacion del usuario')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->relationship('employee', 'name')
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->name . ' ' . $record->lastname) // Concatenar campos
                            ->preload()
                            ->required()
                            ->searchable()
                            ->inlineLabel()
                            ->label('Empleado'),
                        Forms\Components\TextInput::make('name')
                            ->label('Usuario')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->inlineLabel()
                            ->label('Fecha verificaión'),
                        Forms\Components\TextInput::make('password')
                            ->password()
//                        ->rules(function ($record){
//                            return [
//                                $record ? 'nullable' : 'required',
//                                'confirmed',
//                            ];
//                        })
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Empleado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Usuario')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
//                Tables\Columns\TextColumn::make('email_verified_at')
//                    ->dateTime()
//                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                ActivityLogTimelineTableAction::make('Activities'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ActivitylogRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
