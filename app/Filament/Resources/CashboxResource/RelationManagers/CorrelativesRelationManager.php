<?php

namespace App\Filament\Resources\CashBoxResource\RelationManagers;

use App\Models\CashBoxCorrelative;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Livewire\Component;

class CorrelativesRelationManager extends RelationManager
{
    protected static string $relationship = 'correlatives';
    protected static ?string $label = "Correlativos";

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cashBox.description')
                    ->relationship('cashBox', 'description')
                    ->searchable()
                    ->preload()
                    ->default(function () {
                        return $this->ownerRecord->id ?? null;
                    })
                    ->disabled()
                    ->label('Caja'),


                Forms\Components\Select::make('document_type_id')
                    ->relationship('document_type', 'name')
                    ->searchable()
                    ->label('Tipo de Documento')
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('serie')
                    ->required()
                    ->label('Serie')
                    ->maxLength(255),
                Forms\Components\TextInput::make('start_number')
                    ->required()
                    ->label('Número Inicial')
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('end_number')
                    ->required()
                    ->label('Número Final')
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('current_number')
                    ->required()
                    ->label('Número Actual')
                    ->numeric()
                    ->default(1),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cashBox.description')
                    ->label('Caja')
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_type.name')
                    ->label('Tipo de Documento')
                    ->sortable(),
                Tables\Columns\TextColumn::make('serie')
                    ->label('Serie')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_number')
                    ->label('Número Inicial')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_number')
                    ->label('Número Final')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_number')
                    ->label('Número Actual')
                    ->numeric()
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalWidth('5xl')
                    ->modalHeading('Agregar Tiraje')
                    ->label('Agregar Tiraje')
                    ->before(function (createAction $action,array $data) {
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
            ->actions([
                Tables\Actions\EditAction::make()
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


}
