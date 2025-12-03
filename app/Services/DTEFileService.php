<?php

namespace App\Services;

use App\Models\HistoryDte;
use App\Models\Sale;
use Illuminate\Support\Facades\Storage;

class DTEFileService
{
    /**
     * Obtener JSON del DTE desde la base de datos
     */
    public static function getJsonFromDatabase(string $codigoGeneracion): ?array
    {
        $historyDte = HistoryDte::where('codigoGeneracion', $codigoGeneracion)->first();
        
        if (!$historyDte || !$historyDte->dte) {
            return null;
        }

        return is_array($historyDte->dte) ? $historyDte->dte : json_decode($historyDte->dte, true);
    }

    /**
     * Obtener JSON del DTE por ID de venta
     */
    public static function getJsonBySaleId(int $saleId): ?array
    {
        $historyDte = HistoryDte::where('sales_invoice_id', $saleId)->first();
        
        if (!$historyDte || !$historyDte->dte) {
            return null;
        }

        return is_array($historyDte->dte) ? $historyDte->dte : json_decode($historyDte->dte, true);
    }

    /**
     * Crear archivo JSON temporal para impresión/envío
     * Retorna la ruta del archivo temporal
     */
    public static function createTempJsonFile(string $codigoGeneracion): ?string
    {
        $jsonData = self::getJsonFromDatabase($codigoGeneracion);
        
        if (!$jsonData) {
            return null;
        }

        $tempPath = "temp/dte_{$codigoGeneracion}.json";
        Storage::disk('local')->put($tempPath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return Storage::disk('local')->path($tempPath);
    }

    /**
     * Crear archivo JSON temporal desde datos directos
     */
    public static function createTempJsonFromData(array $jsonData, string $codigoGeneracion): string
    {
        $tempPath = "temp/dte_{$codigoGeneracion}.json";
        Storage::disk('local')->put($tempPath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return Storage::disk('local')->path($tempPath);
    }

    /**
     * Eliminar archivo temporal
     */
    public static function deleteTempFile(string $codigoGeneracion): void
    {
        $tempPath = "temp/dte_{$codigoGeneracion}.json";
        if (Storage::disk('local')->exists($tempPath)) {
            Storage::disk('local')->delete($tempPath);
        }
    }

    /**
     * Limpiar todos los archivos temporales de DTE
     */
    public static function cleanTempFiles(): int
    {
        $files = Storage::disk('local')->files('temp');
        $count = 0;
        
        foreach ($files as $file) {
            if (str_starts_with(basename($file), 'dte_')) {
                Storage::disk('local')->delete($file);
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Verificar si el DTE existe en la base de datos
     */
    public static function existsInDatabase(string $codigoGeneracion): bool
    {
        return HistoryDte::where('codigoGeneracion', $codigoGeneracion)->exists();
    }

    /**
     * Crear múltiples archivos temporales para ZIP
     * Retorna array con las rutas de los archivos
     */
    public static function createTempFilesForZip(array $codigosGeneracion): array
    {
        $tempFiles = [];
        
        foreach ($codigosGeneracion as $codigo) {
            $tempPath = self::createTempJsonFile($codigo);
            if ($tempPath) {
                $tempFiles[$codigo] = $tempPath;
            }
        }
        
        return $tempFiles;
    }

    /**
     * Eliminar múltiples archivos temporales
     */
    public static function deleteTempFiles(array $codigosGeneracion): void
    {
        foreach ($codigosGeneracion as $codigo) {
            self::deleteTempFile($codigo);
        }
    }
}
