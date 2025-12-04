<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Models\Branch;
use DateMalformedStringException;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    /**
     * @throws DateMalformedStringException
     */
    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            // Add your form fields here
            Section::make('')
                ->compact()
                ->schema([
                    Select::make('whereHouse')
                        ->label('Sucursal')
                        ->inlineLabel(false)
                        ->placeholder('Seleccione una sucursal')
                        ->options(function () {
                            return Branch::pluck('name', 'id');
                        })
                        ->default(function () {
                            return auth()->user()->employee->branch_id;
                        }),
                    DatePicker::make('startDate')
                        ->default(now())->label('Desde')
                        ->inlineLabel(false),
                    DatePicker::make('endDate')
                        ->label('Desde')
                        ->default(now())
                        ->inlineLabel(false),
                ])->columns(3)


        ]);
    }

}