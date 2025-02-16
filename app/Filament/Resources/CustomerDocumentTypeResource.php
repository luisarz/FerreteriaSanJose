<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerDocumentTypeResource\Pages;
use App\Filament\Resources\CustomerDocumentTypeResource\RelationManagers;
use App\Models\CustomerDocumentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerDocumentTypeResource extends Resource
{
    protected static ?string $model = CustomerDocumentType::class;
    protected static ?string $label = 'Cat-022 T.  Doc. Cliente';
    protected static ?string $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 22;

//    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de Tipo de Documento del Cliente')
                ->columns(1)
                    ->compact()
                    ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Código')
                        ->required()
                        ->maxLength(5),
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(150),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true)
                        ->required(),
                ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->badge()
                    ->color('danger')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
//                Tables\Actions\ViewAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\DeleteAction::make()->label('')->iconSize(IconSize::Medium),
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
            'index' => Pages\ListCustomerDocumentTypes::route('/'),
//            'create' => Pages\CreateCustomerDocumentType::route('/create'),
//            'edit' => Pages\EditCustomerDocumentType::route('/{record}/edit'),
        ];
    }
}
