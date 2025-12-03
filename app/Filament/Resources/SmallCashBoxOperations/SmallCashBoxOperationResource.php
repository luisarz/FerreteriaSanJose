<?php

namespace App\Filament\Resources\SmallCashBoxOperations;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\SmallCashBoxOperations\Pages\ListSmallCashBoxOperations;
use App\Filament\Resources\SmallCashBoxOperationResource\Pages;
use App\Filament\Resources\SmallCashBoxOperationResource\RelationManagers;
use App\Models\SmallCashBoxOperation;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
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
    protected static string | \UnitEnum | null $navigationGroup = 'Caja Chica';
    protected static bool $softDelete = true;


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                    ->compact()
                    ->schema([
                        Select::make('cash_box_open_id') // Este es el campo relacionado en tu modelo
                        ->relationship('cashBoxOpen', 'name', function ($query) {
                            $query->with('cashbox')->where('status','open');
                        })
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->cashbox->description ?? '') // Mostrar el nombre de la caja
                            ->required(),


                        Select::make('employ_id')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('operation')
                            ->label('Operación')
                            ->options([
                                'Ingreso' => 'Ingreso',
                                'Egreso' => 'Egreso',])
                            ->default('Ingreso')
                            ->required(),
                        TextInput::make('amount')
                            ->label('Monto')
                            ->required()
                            ->numeric(),
                        TextInput::make('concept')
                            ->label('Concepto')
                            ->required()
                            ->inlineLabel(false)
                            ->columnSpanFull()
                            ->maxLength(255),
                        FileUpload::make('voucher')
                            ->disk('public')
                            ->visibility('public')
                            ->label('Comprobante')
                            ->directory('vouchers')
                            ->columnSpanFull(),
                        Toggle::make('status')
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
                ImageColumn::make('voucher')
                    ->disk('public')
                    ->circular()
                    ->label('Comprobante')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cashBoxOpen.cashbox.description')
                    ->label('Caja')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.full_name')
                    ->label('Empleado')
                    ->sortable(),
                TextColumn::make('operation')
                ->label('Operación')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->sortable(),
                TextColumn::make('concept')
                    ->label('Concepto')
                    ->searchable(),
                IconColumn::make('status')
                    ->label('Activa')
                    ->boolean(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->label('Archivada')
                    ->placeholder('Activa')
                    ->sortable(),
//                    ->toggleable(isToggledHiddenByDefault: true),

//                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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


                SelectFilter::make('operation')
                    ->options([
                        'Ingreso' => 'Ingreso',
                        'Egreso' => 'Egreso',
                    ]),
                TrashedFilter::make('dele')
                    ->label('Ver eliminados'),

            ])
            ->recordActions([
                ViewAction::make()->label(''),
//                Tables\Actions\RestoreAction::make('restore'),
                DeleteAction::make()->label('')
                        ->before(function ($record,DeleteAction $action) {
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
            'index' => ListSmallCashBoxOperations::route('/'),
//            'create' => Pages\CreateSmallCashBoxOperation::route('/create'),
//            'edit' => Pages\EditSmallCashBoxOperation::route('/{record}/edit'),
        ];
    }
}
