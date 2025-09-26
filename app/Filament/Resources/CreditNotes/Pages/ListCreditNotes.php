<?php

namespace App\Filament\Resources\CreditNotes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CreditNotes\CreditNoteResource;
use App\Models\CashBoxOpen;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCreditNotes extends ListRecords
{
    protected static string $resource = CreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva Nota')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->visible(function () {
                    $whereHouse = auth()->user()->employee->branch_id ?? null;
                    if ($whereHouse) {
                        $cashBoxOpened = CashBoxOpen::with('cashbox')
                            ->where('status', 'open')
                            ->whereHas('cashbox', function ($query) use ($whereHouse) {
                                $query->where('branch_id', $whereHouse);
                            })
                            ->first();
                        if ($cashBoxOpened) {
                            return true;
                        } else {
                            return false;

                        }

                    }


                }),
        ];
    }
}
