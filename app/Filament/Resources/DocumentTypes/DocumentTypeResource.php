<?php

namespace App\Filament\Resources\DocumentTypes;

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
use App\Filament\Resources\DocumentTypes\Pages\ListDocumentTypes;
use App\Filament\Resources\DocumentTypeResource\Pages;
use App\Filament\Resources\DocumentTypeResource\RelationManagers;
use App\Models\DocumentType;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use mysql_xdevapi\Schema;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;
protected static ?string $label = 'Cat-002 T. D. Tributario';
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort=2;

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                Section::make('Información del tipo de documento')
                ->compact()
                    ->schema([
                    TextInput::make('code')
                        ->label('Código')
                        ->inlineLabel(false)
                        ->maxLength(255)
                        ->default(null),
                    TextInput::make('name')
                        ->label('Nombre')
                        ->inlineLabel(false)

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
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                IconColumn::make('is_active')
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
            'index' => ListDocumentTypes::route('/'),
//            'create' => Pages\CreateDocumentType::route('/create'),
//            'edit' => Pages\EditDocumentType::route('/{record}/edit'),
        ];
    }
}
