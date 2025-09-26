<?php

namespace App\Filament\Resources\RetentionTaxes;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\RetentionTaxes\Pages\ListRetentionTaxes;
use App\Filament\Resources\RetentionTaxeResource\Pages;
use App\Filament\Resources\RetentionTaxeResource\RelationManagers;
use App\Models\RetentionTaxe;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RetentionTaxeResource extends Resource
{
    protected static ?string $model = RetentionTaxe::class;

protected static ?string $label = 'Cat-006 Retención IVA';
protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
protected static ?int $navigationSort = 6;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del impuesto')
                    ->compact()
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->label('Código del impuesto')
                            ->inlineLabel()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label('Nombre del impuesto')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_percentage')
                            ->label('Es Porcentaje')
                            ->inlineLabel()
                            ->required()
                            ->reactive(),
                        TextInput::make('rate')
                            ->label('Valor del impuesto')
                            ->prefix(fn (callable $get) => $get('is_percentage') ? '%' : '$')
                            ->inlineLabel()
                            ->required()
                            ->numeric()
                            ->default(0.00),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->required(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                IconColumn::make('is_percentage')
                    ->label('Es Porcentaje')
                    ->boolean(),
                TextColumn::make('rate')
                    ->label('Valor')
                    ->suffix(fn ($state, $record) => $record->is_percentage ?' %' :' $' )
                    ->color('danger')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
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
                EditAction::make()->label('')->iconSize(IconSize::Medium),
                DeleteAction::make()->label('')->iconSize(IconSize::Medium),
                RestoreAction::make()->label('')->iconSize(IconSize::Medium),
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
            'index' => ListRetentionTaxes::route('/'),
//            'create' => Pages\CreateRetentionTaxe::route('/create'),
//            'edit' => Pages\EditRetentionTaxe::route('/{record}/edit'),
        ];
    }
}
