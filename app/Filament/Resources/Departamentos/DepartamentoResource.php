<?php

namespace App\Filament\Resources\Departamentos;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Departamentos\Pages\ListDepartamentos;
use App\Filament\Resources\DepartamentoResource\Pages;
use App\Filament\Resources\DepartamentoResource\RelationManagers;
use App\Models\Departamento;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\MarkdownEditor;

class DepartamentoResource extends Resource
{
    protected static ?string $model = Departamento::class;
    protected static  ?string $label= 'Cat-012 Departamentos';
    protected static ?bool $softDelete = true;
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                ->schema([
                    TextInput::make('code')
                        ->label('Código')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true)
                        ->required(),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
//                Tables\Actions\ActionGroup::make([
                EditAction::make()->label('')->iconSize(IconSize::Medium),
                ReplicateAction::make()->label('')->iconSize(IconSize::Medium)->color('success'),
                ViewAction::make()->label('')->iconSize(IconSize::Medium),
//                ]),
                DeleteAction::make()->label('')->iconSize(IconSize::Medium),
            ],position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartamentos::route('/'),
            // 'create' => Pages\CreateDepartamento::route('/create'),
            // 'edit' => Pages\EditDepartamento::route('/{record}/edit'),
        ];
    }
}
