<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Distrito;
use App\Models\DteTransmisionWherehouse;
use App\Models\Employee;
use App\Models\Municipality;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\ValidationException;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use NunoMaduro\Collision\Adapters\Phpunit\State;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $label = 'Empleados';
    protected static ?string $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Empleado')
                    ->columns(1)
                    ->tabs([
                        Tabs\Tab::make('Datos Personales')
                            ->icon('heroicon-o-user')
                            ->columns(23)
                            ->schema([
                                Forms\Components\Card::make('Datos Laborales')
//                                    ->description('Información Sucursal y Cargo')
                                    ->icon('heroicon-o-briefcase')
                                    ->compact()
                                    ->columns(2)
                                    ->schema([

                                        Forms\Components\Select::make('branch_id')
                                            ->label('Sucursal')
                                            ->relationship('wherehouse', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\Select::make('job_title_id')
                                            ->label('Cargo')
                                            ->relationship('job', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),


                                    ])->columnSpanFull(true),


                                Forms\Components\Card::make('Datos Personales')
//                                            ->description('Datos Personales')
                                    ->icon('heroicon-o-user')
                                    ->compact()
                                    ->columns()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('lastname')
                                            ->label('Apellido')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\DatePicker::make('birthdate')
                                            ->label('Fecha de Nacimiento')
                                            ->inlineLabel(true),

                                        Forms\Components\Select::make('gender')
                                            ->label('Género')
                                            ->options([
                                                'M' => 'Masculino',
                                                'F' => 'Femenino',
                                            ])
                                            ->required(),

                                        Forms\Components\TextInput::make('dui')
                                            ->maxLength(255)
                                            ->required()
                                            ->minLength(9)
                                            ->rules(function ($record) {
                                                return [
                                                    'required',
                                                    'string',
                                                    'max:20',
                                                    'unique:employees,dui,' . ($record ? $record->id : 'NULL'), // Ignora el registro actual
                                                ];
                                            })
                                            ->validationMessages([
                                                'unique' => 'El :attribute Ya ha sido registrado.',
                                                'min' => 'El :attribute debe tener mínimo :min caractreres.',
                                                'required' => 'El :attribute es requerido.',
                                            ])
                                            ->default(null),
                                        Forms\Components\TextInput::make('nit')
                                            ->maxLength(255)
                                            ->default(null),

                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->rules(function ($record) {
                                                return [
                                                    'required',
                                                    'string',
                                                    'max:100',
                                                    'unique:employees,email,' . ($record ? $record->id : 'NULL'), // Ignora el registro actual
                                                ];
                                            })
                                            ->validationMessages([
                                                'unique' => 'El :attribute Ya ha sido registrado.',
                                                'required' => 'El :attribute es requerido.',
                                            ])
                                            ->maxLength(255),
                                        Forms\Components\FileUpload::make('photo')
//                                                        ->inlineLabel()
                                            ->columnSpanFull()
                                            ->label('Foto')
                                            ->directory('employees'),

                                    ]),

                            ]),
                        Tabs\Tab::make('Información complementaria')
                            ->icon('heroicon-o-map-pin')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Card::make('Datos de contacto')
                                    ->description('')
                                    ->icon('heroicon-o-map-pin')
                                    ->compact()
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('department_id')
                                            ->relationship('departamento', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, $set) {
                                                if (!$state) {
                                                    $set('distrito_id', null);
                                                }
                                            })
                                            ->required(),
                                        Forms\Components\Select::make('distrito_id')
                                            ->label('Municipio')
                                            ->options(function (callable $get) {
                                                $department = $get('department_id');
                                                if ($department) {
                                                    return Distrito::where('departamento_id', $department)->pluck('name', 'id');
                                                }
                                                return [];
                                            })
                                            ->live()
                                            ->afterStateUpdated(function ($state, $set) {
                                                if (!$state) {
                                                    $set('municipalitie_id', null);
                                                }
                                            })
                                            ->preload()
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\Select::make('municipalitie_id')
                                            ->label('Distrito')
                                            ->options(function (callable $get) {
                                                $distrito = $get('distrito_id');
                                                if ($distrito) {
                                                    return Municipality::where('distrito_id', $distrito)->pluck('name', 'id');
                                                }
                                                return [];
                                            })
                                            ->preload()
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\TextInput::make('address')
                                            ->required()
                                            ->label('Dirección')
                                            ->maxLength(255),
                                    ]),
                                Forms\Components\Card::make('Configuración')
                                    ->columns(3)
                                    ->schema([

                                        Forms\Components\Toggle::make('is_comisioned')
                                            ->label('Comision por venta')
                                            ->required(),
                                        Forms\Components\TextInput::make('comision')
                                            ->prefix('%')
                                            ->label('Comision')
                                            ->numeric()
                                            ->default(null),
                                        Forms\Components\Toggle::make('is_active')
                                            ->default(true)
                                            ->required(),
                                    ])
                            ]),
                        Tabs\Tab::make('Datos de Familiares')
                            ->icon('heroicon-o-phone')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Card::make('Datos Familiares')
                                    ->description('Datos Familiares')
                                    ->icon('heroicon-o-briefcase')
                                    ->compact()
                                    ->columns(2)
                                    ->schema([

                                        Forms\Components\Select::make('marital_status')
                                            ->label('Estado Civil')
                                            ->options([
                                                'Soltero/a' => 'Soltero/a',
                                                'Casado/a' => 'Casado/a',
                                                'Divorciado/a' => 'Divorciado/a',
                                                'Viudo/a' => 'Viudo/a',
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('marital_name')
                                            ->maxLength(255)
                                            ->label('Nombre Conyugue')
                                            ->default(null),
                                        Forms\Components\TextInput::make('marital_phone')
                                            ->label('Telefono Conyugue')
                                            ->tel()
                                            ->maxLength(255)
                                            ->default(null),
                                    ]),

                            ]),
                    ])->columnSpanFull(),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lastname')
                    ->searchable(),
//                Tables\Columns\TextColumn::make('email')
//                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('birthdate')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender'),
                Tables\Columns\TextColumn::make('marital_status')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('marital_name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('marital_phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('dui')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('departamento.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('municipio.name')
                    ->label('Distrito')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('municipio.name')
                    ->label('Municipio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wherehouse.name')
                    ->numeric()
                    ->label('Sucursal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_title_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_comisioned')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('Sucursales')->relationship('wherehouse', 'name'),
                Tables\Filters\TrashedFilter::make(),

            ])
            ->actions([
//                Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('select_date_range')
                    ->label('')
                    ->icon('heroicon-s-calendar-date-range')
                    ->color('success')
                    ->iconSize(IconSize::Medium)
                    ->modalWidth('max-w-2xl')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha Inicio')
                            ->default(Carbon::now()->startOfMonth())
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha Fin')
                            ->default(Carbon::now()->endOfMonth())
                            ->required(),
                    ])
                    ->action(function (array $data, $record) {

                        $url = route('employee.sales', [
                            'id_employee' => $record->id,
                            'star_date' => date('d-m-Y', strtotime($data['start_date'])),
                            'end_date' => date('d-m-Y', strtotime($data['end_date']))
                        ]);

                        // Devolver la URL como una respuesta JSON para que el frontend la maneje
                        return \Filament\Notifications\Notification::make()
                            ->title('Reporte de ventas')
                            ->body('Haz clic aquí para ver los resultados.')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('Ver reporte')
                                    ->button()
                                    ->url($url, true) // true = abrir en nueva pestaña
                            ])
                            ->send();
                    })
                ->modalButton('Generar Reporte'),

                Tables\Actions\ViewAction::make()->label('')->iconSize(IconSize::Medium),
//                    Tables\Actions\ReplicateAction::make()->label('')->excludeAttributes(['email','dui','nit','photo','created_at','updated_at','deleted_at']),
                Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\DeleteAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\RestoreAction::make()->label('')->iconSize(IconSize::Medium),

//                ]),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title($exception->getMessage())
            ->danger()
            ->send();
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
