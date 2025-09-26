<?php

namespace App\Filament\Resources\CustomerDocumentTypes;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\CustomerDocumentTypes\Pages\ListCustomerDocumentTypes;
use App\Filament\Resources\CustomerDocumentTypeResource\Pages;
use App\Filament\Resources\CustomerDocumentTypeResource\RelationManagers;
use App\Models\CustomerDocumentType;
use Filament\Forms;
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
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 22;

//    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de Tipo de Documento del Cliente')
                ->columns(1)
                    ->compact()
                    ->schema([
                    TextInput::make('code')
                        ->label('Código')
                        ->required()
                        ->maxLength(5),
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(150),
                    Toggle::make('is_active')
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
                TextColumn::make('code')
                    ->label('Código')
                    ->badge()
                    ->color('danger')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
//                Tables\Actions\ViewAction::make()->label('')->iconSize(IconSize::Medium),
                EditAction::make()->label('')->iconSize(IconSize::Medium),
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
            'index' => ListCustomerDocumentTypes::route('/'),
//            'create' => Pages\CreateCustomerDocumentType::route('/create'),
//            'edit' => Pages\EditCustomerDocumentType::route('/{record}/edit'),
        ];
    }
}
