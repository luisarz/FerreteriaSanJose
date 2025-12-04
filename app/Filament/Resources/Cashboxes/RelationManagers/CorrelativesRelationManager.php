<?php

namespace App\Filament\Resources\Cashboxes\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\CashBoxCorrelative;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Component;

class CorrelativesRelationManager extends RelationManager
{
    protected static string $relationship = 'correlatives';
    protected static ?string $label = "Correlativos";

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cashBox.description')
                    ->relationship('cashBox', 'description')
                    ->searchable()
                    ->preload()
                    ->default(function () {
                        return $this->ownerRecord->id ?? null;
                    })
                    ->disabled()
                    ->label('Caja'),


                Select::make('document_type_id')
                    ->relationship('document_type', 'name')
                    ->searchable()
                    ->label('Tipo de Documento')
                    ->preload()
                    ->required(),
                TextInput::make('serie')
                    ->required()
                    ->label('Serie')
                    ->maxLength(255),
                TextInput::make('start_number')
                    ->required()
                    ->label('Número Inicial')
                    ->numeric()
                    ->default(1),
                TextInput::make('end_number')
                    ->required()
                    ->label('Número Final')
                    ->numeric()
                    ->default(1),
                TextInput::make('current_number')
                    ->required()
                    ->label('Número Actual')
                    ->numeric()
                    ->default(1),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cashBox.description')
                    ->label('Caja')
                    ->sortable(),
                TextColumn::make('document_type.name')
                    ->label('Tipo de Documento')
                    ->sortable(),
                TextColumn::make('serie')
                    ->label('Serie')
                    ->searchable(),
                TextColumn::make('start_number')
                    ->label('Número Inicial')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('end_number')
                    ->label('Número Final')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('current_number')
                    ->label('Número Actual')
                    ->numeric()
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
            ->headerActions([
                CreateAction::make()
                    ->modalWidth('5xl')
                    ->modalHeading('Agregar Tiraje')
                    ->label('Agregar Tiraje')
                    ->before(function (CreateAction $action,array $data) {
                        $cashbox = $this->ownerRecord->id;
                        $documentType = intval($data['document_type_id']);
                        // Query to check if the correlative already exists
                        $correlative = CashBoxCorrelative::where('cash_box_id', $cashbox)
                            ->where('document_type_id', $documentType)
                            ->first();
                        if ($correlative) {
                            Notification::make()
                                ->danger()
                                ->title('Correlativo')
                                ->body('El tipo de documento ya existe en la sucursal')
                                ->send();
                                $action->halt();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                ->before(function (EditAction $action, CashBoxCorrelative $record) {
                   //primero comparar el id del tipo de documento anterior con el nuevo

                    $documentTypeAnterior = $record->document_type_id;
                    $documentTypeNuevo = $action->getFormData()['document_type_id'];
                    if($documentTypeAnterior != $documentTypeNuevo){
                        $cashbox = $this->ownerRecord->id;
                        $documentType = intval($documentTypeNuevo);
                        $correlative = CashBoxCorrelative::where('cash_box_id', $cashbox)
                            ->where('document_type_id', $documentType)
                            ->first();
                        if ($correlative) {
                            Notification::make()
                                ->danger()
                                ->title('Correlativo')
                                ->body('El tipo de documento ya existe en la sucursal')
                                ->send();
                                $action->halt();
                        }
                    }

                }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }


}
