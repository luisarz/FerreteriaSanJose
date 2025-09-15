<?php

namespace App\Filament\Resources\MarcaResource\RelationManagers;

use App\Filament\Exports\ProductExporter;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductosRelationManagerRelationManager extends RelationManager
{
    protected static string $relationship = 'productos';
    protected static ?string $title = 'Productos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('Productos')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Productos')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Codigo')
                    ->sortable()
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
//                                    ->weight(FontWeight::SemiBold)
                    ->sortable()
//                                    ->icon('heroicon-s-cube')
                    ->wrap()
//                                    ->formatStateUsing(fn($state, $record) => $record->deleted_at ? "<span style='text-decoration: line-through; color: red;'>$state</span>" : $state)
                    ->html()
                    ->searchable(),
                Tables\Columns\TextColumn::make('unitMeasurement.description')
                    ->label('Presentación')
//                    ->icon('heroicon-s-scale')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Linea')
//                    ->icon('heroicon-s-wrench-screwdriver')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->copyable()
//                                    ->icon('heroicon-s-qr-code')
                    ->copyMessage('SKU  copied')
                    ->searchable(),
                Tables\Columns\BooleanColumn::make('is_grouped')
                    ->label('Servicio')
                    ->trueIcon('heroicon-o-server-stack')
                    ->falseIcon('heroicon-o-server')
                    ->sortable(),


                Tables\Columns\TextColumn::make('bar_code')
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
            ->actions([
//                Tables\Actions\EditAction::make(),
//                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ExportBulkAction::make('Export')->exporter(ProductExporter::class),
                ]),
            ]);
    }
}
