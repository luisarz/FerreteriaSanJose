<?php

namespace App\Filament\Resources\Employees;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Distrito;
use App\Models\DteTransmisionWherehouse;
use App\Models\Employee;
use App\Models\Municipality;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
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
    protected static string | \UnitEnum | null $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Empleado')
                    ->columns(1)
                    ->tabs([
                        \Filament\Schemas\Components\Tabs\Tab::make('Datos Personales')
                            ->icon('heroicon-o-user')
                            ->columns(23)
                            ->schema([
                                Section::make('Datos Laborales')
//                                    ->description('Información Sucursal y Cargo')
                                    ->icon('heroicon-o-briefcase')
                                    ->compact()
                                    ->columns(2)
                                    ->schema([

                                        Select::make('branch_id')
                                            ->label('Sucursal')
                                            ->relationship('wherehouse', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->required(),
                                        Select::make('job_title_id')
                                            ->label('Cargo')
                                            ->relationship('job', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),


                                    ])->columnSpanFull(true),


                                Section::make('Datos Personales')
//                                            ->description('Datos Personales')
                                    ->icon('heroicon-o-user')
                                    ->compact()
                                    ->columns()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('lastname')
                                            ->label('Apellido')
                                            ->required()
                                            ->maxLength(255),
                                        DatePicker::make('birthdate')
                                            ->label('Fecha de Nacimiento')
                                            ->inlineLabel(true),

                                        Select::make('gender')
                                            ->label('Género')
                                            ->options([
                                                'M' => 'Masculino',
                                                'F' => 'Femenino',
                                            ])
                                            ->required(),

                                        TextInput::make('dui')
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
                                        TextInput::make('nit')
                                            ->maxLength(255)
                                            ->default(null),

                                        TextInput::make('phone')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('email')
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
                                        FileUpload::make('photo')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->columnSpanFull()
                                            ->label('Foto')
                                            ->directory('employees'),

                                    ]),

                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Información complementaria')
                            ->icon('heroicon-o-map-pin')
                            ->columns(2)
                            ->schema([
                                Section::make('Datos de contacto')
                                    ->description('')
                                    ->icon('heroicon-o-map-pin')
                                    ->compact()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('department_id')
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
                                        Select::make('distrito_id')
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
                                        Select::make('municipalitie_id')
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
                                        TextInput::make('address')
                                            ->required()
                                            ->label('Dirección')
                                            ->maxLength(255),
                                    ]),
                                Section::make('Configuración')
                                    ->columns(3)
                                    ->schema([

                                        Toggle::make('is_comisioned')
                                            ->label('Comision por venta')
                                            ->required(),
                                        TextInput::make('comision')
                                            ->prefix('%')
                                            ->label('Comision')
                                            ->numeric()
                                            ->default(null),
                                        Toggle::make('is_active')
                                            ->default(true)
                                            ->required(),
                                    ])
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Datos de Familiares')
                            ->icon('heroicon-o-phone')
                            ->columns(2)
                            ->schema([
                                Section::make('Datos Familiares')
                                    ->description('Datos Familiares')
                                    ->icon('heroicon-o-briefcase')
                                    ->compact()
                                    ->columns(2)
                                    ->schema([

                                        Select::make('marital_status')
                                            ->label('Estado Civil')
                                            ->options([
                                                'Soltero/a' => 'Soltero/a',
                                                'Casado/a' => 'Casado/a',
                                                'Divorciado/a' => 'Divorciado/a',
                                                'Viudo/a' => 'Viudo/a',
                                            ])
                                            ->required(),
                                        TextInput::make('marital_name')
                                            ->maxLength(255)
                                            ->label('Nombre Conyugue')
                                            ->default(null),
                                        TextInput::make('marital_phone')
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
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('lastname')
                    ->searchable(),
//                Tables\Columns\TextColumn::make('email')
//                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('address')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('birthdate')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date()
                    ->sortable(),
                TextColumn::make('gender'),
                TextColumn::make('marital_status')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('marital_name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('marital_phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('dui')
                    ->searchable(),
                TextColumn::make('nit')
                    ->searchable(),
                TextColumn::make('departamento.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('municipio.name')
                    ->label('Distrito')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('municipio.name')
                    ->label('Municipio')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('wherehouse.name')
                    ->numeric()
                    ->label('Sucursal')
                    ->sortable(),
                TextColumn::make('job_title_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_comisioned')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
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
                SelectFilter::make('Sucursales')->relationship('wherehouse', 'name'),
                TrashedFilter::make(),

            ])
            ->recordActions([
//                Tables\Actions\ActionGroup::make([
                Action::make('select_date_range')
                    ->label('')
                    ->icon('heroicon-s-calendar-date-range')
                    ->color('success')
                    ->visible(fn ($record) => $record->job_title_id != 4)
                    ->iconSize(IconSize::Medium)
                    ->modalWidth('max-w-2xl')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Fecha Inicio')
                            ->default(Carbon::now()->startOfMonth())
                            ->required(),
                        DatePicker::make('end_date')
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
                        return Notification::make()
                            ->title('Reporte de ventas')
                            ->body('Haz clic aquí para ver los resultados.')
                            ->actions([
                                Action::make('Ver reporte')
                                    ->button()
                                    ->url($url, true) // true = abrir en nueva pestaña
                            ])
                            ->send();
                    })
                ->modalButton('Generar Reporte'),
                Action::make('select_date_range_detail')
                    ->label('')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->iconSize('medium')
                    ->color('success')
                    ->visible(fn ($record) => $record->job_title_id != 4)
                    ->iconSize(IconSize::Medium)
                    ->modalWidth('max-w-2xl')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Fecha Inicio')
                            ->default(Carbon::now()->subWeek())
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Fecha Fin')
                            ->default(Carbon::now()->endOfMonth())
                            ->required(),
                    ])
                    ->action(function (array $data, $record) {

                        $url = route('employee.sales-test', [
                            'id_employee' => $record->id,
                            'star_date' => date('d-m-Y', strtotime($data['start_date'])),
                            'end_date' => date('d-m-Y', strtotime($data['end_date']))
                        ]);

                        // Devolver la URL como una respuesta JSON para que el frontend la maneje
                        return Notification::make()
                            ->title('Reporte de ventas detalle')
                            ->body('Haz clic aquí para ver los resultados.')
                            ->actions([
                                Action::make('Ver reporte')
                                    ->button()
                                    ->url($url, true) // true = abrir en nueva pestaña
                            ])
                            ->send();
                    })
                    ->modalButton('Generar Reporte'),
                Action::make('workReport')
                    ->label('')
                    ->visible(fn ($record) => $record->job_title_id == 4)
                    ->icon('heroicon-s-calendar-date-range')
                    ->color('danger')
                    ->iconSize(IconSize::Medium)
                    ->modalWidth('max-w-2xl')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Fecha Inicio')
                            ->default(Carbon::now()->startOfMonth())
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Fecha Fin')
                            ->default(Carbon::now()->endOfMonth())
                            ->required(),
                    ])
                    ->action(function (array $data, $record) {

                        $url = route('employee.sales-work', [
                            'id_employee' => $record->id,
                            'star_date' => date('d-m-Y', strtotime($data['start_date'])),
                            'end_date' => date('d-m-Y', strtotime($data['end_date']))
                        ]);

                        // Devolver la URL como una respuesta JSON para que el frontend la maneje
                        return Notification::make()
                            ->title('Reporte de manos de Obra')
                            ->body('Haz clic aquí para ver los resultados.')
                            ->actions([
                                Action::make('Ver reporte')
                                    ->button()
                                    ->url($url, true) // true = abrir en nueva pestaña
                            ])
                            ->send();
                    })
                    ->modalButton('Generar Reporte'),

                ViewAction::make()->label('')->iconSize(IconSize::Medium),
//                    Tables\Actions\ReplicateAction::make()->label('')->excludeAttributes(['email','dui','nit','photo','created_at','updated_at','deleted_at']),
                EditAction::make()->label('')->iconSize(IconSize::Medium),
                DeleteAction::make()->label('')->iconSize(IconSize::Medium),
                RestoreAction::make()->label('')->iconSize(IconSize::Medium),

//                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }
}
