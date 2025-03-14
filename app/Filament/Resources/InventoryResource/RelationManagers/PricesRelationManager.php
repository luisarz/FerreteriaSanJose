<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Models\Inventory;
use App\Models\Price;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'Prices';
    protected static ?string $label = "Precios";
    protected static ?string $title = "Precios de venta";


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Precio')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->inlineLabel(false)
                            ->label('Descripción Precio')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('price')
                            ->label('Precio')
                            ->inlineLabel(false)
                            ->required()
                            ->numeric()
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, $record, callable $set, callable $get) {
                                $inventory = Inventory::find($record->inventory_id);

                                if (!$inventory) {
                                    return;
                                }
                                $costo = $inventory->cost_with_taxes;
                                // Validar si el precio de venta es nulo, vacío o cero
                                if (empty($state) || $state <= 0) {
                                    $margenUtilidad = 0;
                                } else {
                                    $margenUtilidad = (($state - $costo) / $state) * 100;
                                }


                                $set('cost', $costo);
                                $set('utilidad', number_format($margenUtilidad, 2));
                            })
                            ->rules(function (?Price $record) {
                                $inventory = Inventory::find($record->inventory_id ?? null); // Cambia 'inventory_id' al nombre correcto del campo de relación
                                $cost = $inventory ? $inventory->cost_with_taxes : 0; // Obtén el costo, o 0 si no hay inventario
                                return [
                                    'required',
                                    'numeric',
                                    'gt:' . $cost,
                                ];
                            }),
                        Forms\Components\TextInput::make('utilidad')
                            ->label('Utilidad')
                            ->inlineLabel(false)
                            ->required()
                            ->numeric()
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, $record, callable $set, callable $get) {
                                $inventory = Inventory::find($record->inventory_id);

                                if (!$inventory) {
                                    return;
                                }
                                $costo = $inventory->cost_with_taxes;
                                // Validar si el precio de venta es nulo, vacío o cero
                                if (empty($state) || $state < 0) {
                                    $precioVenta = 0;
                                } else {
                                    $divisor = 1 - ($state / 100);

                                    // Evitar división por cero
                                    $precioVenta = ($divisor != 0) ? ($costo / $divisor) : 0;
                                }

                                $set('price', number_format($precioVenta, 2));
                            })
                            ->rules(function (?Price $record) {
                                $inventory = Inventory::find($record->inventory_id ?? null); // Cambia 'inventory_id' al nombre correcto del campo de relación
                                $cost = $inventory ? $inventory->cost_with_taxes : 0; // Obtén el costo, o 0 si no hay inventario
                                return [
                                    'required',
                                    'numeric',
                                    'gt:' . $cost,
                                ];
                            }),
//                        Forms\Components\TextInput::make('cost')
//                            ->label('Costo')
//                            ->inlineLabel(false)
//                            ->required()
//                            ->numeric(),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Predeterminado'),
                    ]),
            ]);
        // Asegúrate de que estás usando el modelo correcto

    }

    protected function beforeDelete(DeleteAction $action): void
    {
        $inventoryId = $this->ownerRecord->id;
        dd($inventoryId);
        $pricesCount = Price::where('inventory_id', $inventoryId)->count();
        // Si hay solo un precio, cancelar la eliminación
        if ($pricesCount <= 1) {
            $action->halt(); // Detener la acción de eliminación
            Notification::make()
                ->title('Debe existir al menos un precio.')
                ->danger()
                ->send();
        }
    }

    // Para eliminar en masa
    protected function beforeBulkDelete(DeleteBulkAction $action): void
    {
        $inventoryId = $this->ownerRecord->id;
        $pricesCount = Price::where('inventory_id', $inventoryId)->count();

        // Si hay solo un precio, cancelar la eliminación
        if ($pricesCount <= 1) {
            $action->halt(); // Detener la acción de eliminación en masa
            Notification::make()
                ->title('Debe existir al menos un precio.')
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        $inventory = $this->ownerRecord;
        $branch = $inventory->branch;
        $maxPriceByProduct = $branch->prices_by_products;
        $pricesCount = Price::where('inventory_id', $inventory->id)->count();
        $canCreate = $pricesCount < $maxPriceByProduct;

        return $table
            ->searchable()
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Descripción Precio'),
                Tables\Columns\TextColumn::make('price')
                    ->numeric()
                    ->money('USD', locale: 'en_US')
                    ->label('Precio'),
                Tables\Columns\TextColumn::make('utilidad')
                    ->numeric()
                    ->suffix('%')
                    ->label('Utilidad (%)'),
                Tables\Columns\ToggleColumn::make('is_default')
                    ->label('Predeterminado'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activo'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->visible($canCreate),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function afterSave(): void
    {
        $this->model->precios()->each(function ($precio) {
            if ($precio->is_default) {
                Price::where('inventory_id', $precio->inventory_id)
                    ->where('id', '!=', $precio->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|max:255',
            'price' => [
                'required',
                'numeric',
                'gt:' . ($this->record->inventory->cost ?? 0), // Asegúrate de tener acceso al costo
            ],
        ];
    }

    protected function getValidationMessages(): array
    {
        return [
            'name.required' => 'La descripción del precio es obligatoria.',
            'price.required' => 'El campo Precio es obligatorio.',
            'price.numeric' => 'El campo Precio debe ser un número.',
            'price.gt' => 'El Precio debe ser mayor que el costo del inventario, que es ' . ($this->record->inventory->cost ?? 0) . '.',
        ];
    }

}
