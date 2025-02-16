<?php

namespace App\Filament\Exports;

use App\Models\Inventory;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class InventoryExporter extends Exporter
{
    protected static ?string $model = Inventory::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('product_id'),
            ExportColumn::make('branch_id'),
            ExportColumn::make('stock'),
            ExportColumn::make('stock_min'),
            ExportColumn::make('stock_max'),
            ExportColumn::make('cost_without_taxes'),
            ExportColumn::make('cost_with_taxes'),
            ExportColumn::make('is_stock_alert'),
            ExportColumn::make('is_expiration_date'),
            ExportColumn::make('is_active'),
            ExportColumn::make('deleted_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your inventory export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
