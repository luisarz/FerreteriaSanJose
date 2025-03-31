<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Card;
use App\Models\Distrito;
use App\Models\Municipality;
use Illuminate\Support\Facades\Auth;

class CreateClienteForm
{
    public static function getForm(): array
    {
        return [
            Section::make('Nuevo Cliente')
                ->schema([
                    Card::make('')
                        ->description('Información personal del cliente')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Select::make('person_type_id')
                                ->relationship('persontype', 'name')
                                ->label('Tipo de persona')
                                ->required()
                                ->preload()
                                ->searchable(),
                            Select::make('document_type_id')
                                ->relationship('documenttypecustomer', 'name')
                                ->label('Tipo de documento')
                                ->required()
                                ->preload()
                                ->searchable(),
                            TextInput::make('name')
                                ->required()
                                ->label('Nombre')
                                ->maxLength(255),
                            TextInput::make('last_name')
                                ->label('Apellido')
                                ->maxLength(255),
                        ])->compact()
                        ->columns(2),
                ])->columns(2),

            Section::make('Información Comercial')
                ->compact()
                ->columns(2)
                ->schema([
                    Select::make('economic_activities_id')
                        ->relationship('economicactivity', 'description')
                        ->label('Actividad Económica')
                        ->preload()
                        ->searchable(),
                    Select::make('wherehouse_id')
                        ->relationship('wherehouse', 'name')
                        ->default(fn() => Auth::user()->employee->wherehouse->id)
                        ->label('Sucursal')
                        ->preload()
                        ->searchable()
                        ->required(),
                    TextInput::make('nrc')->maxLength(255)->default(null),
                    TextInput::make('dui')->maxLength(255)->default(null),
                    TextInput::make('nit')->maxLength(255)->default(null),
                    Toggle::make('is_taxed')->label('Paga IVA')->default(true),
                ]),

            Section::make('Información de contacto')
                ->compact()
                ->columns(2)
                ->schema([
                    TextInput::make('email')
                        ->label('Correo')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Teléfono')
                        ->mask('(999) 9999-9999')
                        ->default(503)
                        ->tel()
                        ->maxLength(255),
                    Select::make('country_id')
                        ->label('País')
                        ->relationship('country', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('departamento_id')
                        ->label('Departamento')
                        ->relationship('departamento', 'name')
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn($state, $set) => !$state ? $set(['distrito_id' => null, 'municipio_id' => null]) : null)
                        ->preload(),
                    Select::make('distrito_id')
                        ->label('Municipio')
                        ->live()
                        ->preload()
                        ->searchable()
                        ->afterStateUpdated(fn($state, $set) => !$state ? $set('municipio_id', null) : null)
                        ->options(fn(callable $get) => $get('departamento_id')
                            ? Distrito::where('departamento_id', $get('departamento_id'))->pluck('name', 'id')
                            : []
                        ),
                    Select::make('municipio_id')
                        ->label('Distrito')
                        ->options(fn(callable $get) => $get('distrito_id')
                            ? Municipality::where('distrito_id', $get('distrito_id'))->pluck('name', 'id')
                            : []
                        ),
                ]),

            Section::make('Información general')
                ->compact()
                ->columns(2)
                ->schema([
                    TextInput::make('address')->label('Dirección')->maxLength(255)->default(null),
                    Toggle::make('is_credit_client')->label('Cliente de crédito')->required(),
                    TextInput::make('credit_limit')->label('Límite de crédito')->numeric()->default(null),
                    TextInput::make('credit_days')->label('Días de crédito')->numeric()->default(null),
                    TextInput::make('credit_balance')->label('Saldo de crédito')->numeric()->default(null),
                    DatePicker::make('last_purched')->label('Última compra')->inlineLabel(true)->default(null),
                    Toggle::make('is_active')->default(true)->required(),
                ]),
        ];
    }
}
