<?php

namespace App\Filament\Resources\Marcas\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use App\Filament\Exports\ProductExporter;
use App\Models\Product;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductosRelationManagerRelationManager extends RelationManager
{
    protected static string $relationship = 'productos';
    protected static ?string $title = 'Productos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('Productos')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Productos')
            ->columns([
                TextColumn::make('id')
                    ->label('Codigo')
                    ->sortable()
                    ->wrap()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Producto')
//                                    ->weight(FontWeight::SemiBold)
                    ->sortable()
//                                    ->icon('heroicon-s-cube')
                    ->wrap()
//                                    ->formatStateUsing(fn($state, $record) => $record->deleted_at ? "<span style='text-decoration: line-through; color: red;'>$state</span>" : $state)
                    ->html()
                    ->searchable(),
                TextColumn::make('unitMeasurement.description')
                    ->label('Presentación')
//                    ->icon('heroicon-s-scale')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Linea')
//                    ->icon('heroicon-s-wrench-screwdriver')
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->copyable()
//                                    ->icon('heroicon-s-qr-code')
                    ->copyMessage('SKU  copied')
                    ->searchable(),
                BooleanColumn::make('is_grouped')
                    ->label('Servicio')
                    ->trueIcon('heroicon-o-server-stack')
                    ->falseIcon('heroicon-o-server')
                    ->sortable(),


                TextColumn::make('bar_code')
//                    ->icon('heroicon-s-code-bracket-square')
                    ->label('C. Barras')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
//                Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
//                Tables\Actions\EditAction::make(),
//                Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make('Export')->exporter(ProductExporter::class),
                ]),
            ]);
    }
}
