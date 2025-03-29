<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmallCashBoxOperationResource\Pages;
use App\Filament\Resources\SmallCashBoxOperationResource\RelationManagers;
use App\Models\SmallCashBoxOperation;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class SmallCashBoxOperationResource extends Resource
{
    protected static ?string $model = SmallCashBoxOperation::class;
    protected static ?string $label = 'Transacciones';
    protected static ?string $navigationGroup = 'Caja Chica';
    protected static bool $softDelete = true;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                    ->compact()
                    ->schema([
                        Forms\Components\Select::make('cash_box_open_id') // Este es el campo relacionado en tu modelo
                        ->relationship('cashBoxOpen', 'name', function ($query) {
                            $query->with('cashbox')->where('status','open');
                        })
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->cashbox->description ?? '') // Mostrar el nombre de la caja
                            ->required(),


                        Forms\Components\Select::make('employ_id')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('operation')
                            ->label('Operación')
                            ->options([
                                'Ingreso' => 'Ingreso',
                                'Egreso' => 'Egreso',])
                            ->default('Ingreso')
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('concept')
                            ->label('Concepto')
                            ->required()
                            ->inlineLabel(false)
                            ->columnSpanFull()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('voucher')
                            ->label('Comprobante')
                            ->directory('vouchers')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('status')
                            ->label('Operación activa')
                            ->default(true)
                            ->required(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('voucher')
                    ->circular()
                    ->label('Comprobante')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cashBoxOpen.cashbox.description')
                    ->label('Caja')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Empleado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('operation')
                ->label('Operación')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->sortable(),
                Tables\Columns\TextColumn::make('concept')
                    ->label('Concepto')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Activa')
                    ->boolean(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->label('Archivada')
                    ->placeholder('Activa')
                    ->sortable(),
//                    ->toggleable(isToggledHiddenByDefault: true),

//                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->timePicker24()
                    ->startDate(\Carbon\Carbon::now())
                    ->endDate(Carbon::now())
                    ->label('Fecha de Operación'),


                Tables\Filters\SelectFilter::make('operation')
                    ->options([
                        'Ingreso' => 'Ingreso',
                        'Egreso' => 'Egreso',
                    ]),
                Tables\Filters\TrashedFilter::make('dele')
                    ->label('Ver eliminados'),

            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(''),
//                Tables\Actions\RestoreAction::make('restore'),
                Tables\Actions\DeleteAction::make()->label('')
                        ->before(function ($record,Tables\Actions\DeleteAction $action) {
                        $operationType = $record->operation;
                        $amount = $record->amount;
                        $caja = SmallCashBoxOperation::with('cashBoxOpen')
                            ->where('id', $record->id)->first();
                        if (!$caja) {
                            Notification::make()
                                ->title('No hay caja abierta')
                                ->body('No se puede realizar la operación')
                                ->danger()
                                ->icon('x-circle')
                                ->send();
//                            $this->halt()->stop();
                            $action->cancel();
                        }
                        $cashBox = $caja->cashBoxOpen->cashbox;
                        if ($operationType === 'Egreso') {

                            $cashBox->balance += $amount;
                        } elseif ($operationType === 'Ingreso') {
                            if ($cashBox->balance < $amount) {
                                Notification::make()
                                    ->title('Fondos insuficientes')
                                    ->body('No se puede realizar la operación')
                                    ->danger()
                                    ->iconColor('danger')
                                    ->icon('heroicon-o-x-circle')
                                    ->send();
//                                $this->halt()->stop();
                                $action->cancel();

                            }
                            $cashBox->balance -= $amount;
                        }
                        // Guardar el nuevo balance
                        $cashBox->save();
                    })])
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
            'index' => Pages\ListSmallCashBoxOperations::route('/'),
//            'create' => Pages\CreateSmallCashBoxOperation::route('/create'),
//            'edit' => Pages\EditSmallCashBoxOperation::route('/{record}/edit'),
        ];
    }
}
