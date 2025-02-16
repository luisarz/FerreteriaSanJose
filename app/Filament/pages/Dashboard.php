<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    /**
     * @throws \DateMalformedStringException
     */
    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            // Add your form fields here
            Section::make('')
                ->compact()
                ->schema([
                    Select::make('whereHouse')
                        ->inlineLabel(false)
                        ->placeholder('Seleccione una sucursal')
                        ->options(function () {
                            return \App\Models\Branch::pluck('name', 'id');
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