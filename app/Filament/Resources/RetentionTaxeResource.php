<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RetentionTaxeResource\Pages;
use App\Filament\Resources\RetentionTaxeResource\RelationManagers;
use App\Models\RetentionTaxe;
use Filament\Forms;
use Filament\Forms\Form;
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
protected static ?string $navigationGroup = 'Catálogos Hacienda';
protected static ?int $navigationSort = 6;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del impuesto')
                    ->compact()
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->label('Código del impuesto')
                            ->inlineLabel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del impuesto')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_percentage')
                            ->label('Es Porcentaje')
                            ->inlineLabel()
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('rate')
                            ->label('Valor del impuesto')
                            ->prefix(fn (callable $get) => $get('is_percentage') ? '%' : '$')
                            ->inlineLabel()
                            ->required()
                            ->numeric()
                            ->default(0.00),
                        Forms\Components\Toggle::make('is_active')
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
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_percentage')
                    ->label('Es Porcentaje')
                    ->boolean(),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Valor')
                    ->suffix(fn ($state, $record) => $record->is_percentage ?' %' :' $' )
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
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
                Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\DeleteAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\RestoreAction::make()->label('')->iconSize(IconSize::Medium),
            ],position: Tables\Enums\ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListRetentionTaxes::route('/'),
//            'create' => Pages\CreateRetentionTaxe::route('/create'),
//            'edit' => Pages\EditRetentionTaxe::route('/{record}/edit'),
        ];
    }
}
