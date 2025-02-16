<?php

namespace App\Filament\Exports;

use App\Models\Sale;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SaleExporter extends Exporter
{
    protected static ?string $model = Sale::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('operation_date'),
            ExportColumn::make('documentType.name'),
            ExportColumn::make('document_internal_number'),
            ExportColumn::make('wherehouse.name'),
            ExportColumn::make('seller.name'),
            ExportColumn::make('customer.name'),
            ExportColumn::make('operation_condition_id'),
            ExportColumn::make('paymentMethod.name'),
            ExportColumn::make('sales_payment_status'),
            ExportColumn::make('status'),
            ExportColumn::make('is_taxed'),
            ExportColumn::make('have_retention'),
            ExportColumn::make('net_amount'),
            ExportColumn::make('taxe'),
            ExportColumn::make('discount'),
            ExportColumn::make('retention'),
            ExportColumn::make('sale_total'),
            ExportColumn::make('cash'),
            ExportColumn::make('change'),
            ExportColumn::make('casher.name'),
            ExportColumn::make('is_dte'),
            ExportColumn::make('generationCode'),
            ExportColumn::make('receiptStamp'),
            ExportColumn::make('jsonUrl'),
            ExportColumn::make('is_order'),
            ExportColumn::make('is_order_closed_without_invoiced'),
            ExportColumn::make('is_invoiced_order'),
            ExportColumn::make('order_number'),
            ExportColumn::make('deleted_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your sale export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
