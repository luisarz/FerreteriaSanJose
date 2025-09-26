<?php

namespace App\Filament\Resources\Inventories\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use App\Models\Inventory;
use App\Models\Price;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'Prices';
    protected static ?string $label = "Precios";
    protected static ?string $title = "Precios de venta";


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Precio')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->inlineLabel(false)
                            ->label('Descripción Precio')
                            ->maxLength(255),
                        TextInput::make('price')
                            ->label('Precio')
                            ->inlineLabel(false)
                            ->required()
                            ->numeric()
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, $record, callable $set, callable $get) {
                                $inventory = $this->getOwnerRecord();

                                if (!$inventory) {
                                    return;
                                }
                                $costo = $inventory->cost_without_taxes;
                                // Validar si el precio de venta es nulo, vacío o cero
                                if (empty($state) || $state <= 0) {
                                    $margenUtilidad = 0;
                                } else {
                                    $margenUtilidad = (($state - $costo) / $state) * 100;
                                }


                                $set('cost', $costo);
                                $set('utilidad', number_format($margenUtilidad, 2));
                            }),

                        TextInput::make('utilidad')
                            ->label('Utilidad')
                            ->inlineLabel(false)
                            ->required()
                            ->numeric()
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, $record, callable $set, callable $get) {
                                $inventory = $this->getOwnerRecord();

                                if (!$inventory) {
                                    return;
                                }
                                $costo = $inventory->cost_without_taxes;
                                // Validar si el precio de venta es nulo, vacío o cero
                                if (empty($state) || $state < 0) {
                                    $precioVenta = 0;
                                } else {
                                    $divisor = 1 - ($state / 100);
                                    // Evitar división por cero
                                    $precioVenta = ($divisor != 0) ? ($costo / $divisor) : 0;
                                }

                                $set('price', number_format($precioVenta, 2));
                            }),

//                        Forms\Components\TextInput::make('cost')
//                            ->label('Costo')
//                            ->inlineLabel(false)
//                            ->required()
//                            ->numeric(),

                        Toggle::make('is_default')
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
                TextColumn::make('name')
                    ->searchable()
                    ->label('Descripción Precio'),
                TextColumn::make('price')
                    ->numeric()
                    ->money('USD', locale: 'en_US')
                    ->label('Precio'),
                TextColumn::make('utilidad')
                    ->numeric()
                    ->money('USD', locale: 'en_US')
                    ->suffix('%')
                    ->label('Utilidad (%)'),
                ToggleColumn::make('is_default')
                    ->label('Predeterminado'),
                ToggleColumn::make('is_active')
                    ->label('Activo'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->visible($canCreate),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
