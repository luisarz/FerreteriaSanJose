<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Tribute;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Obtener configuración de la empresa con caché
     */
    public static function getCompany(): ?Company
    {
        return Cache::remember('company_config', 3600, function () {
            return Company::find(1);
        });
    }

    /**
     * Obtener todos los tributos con caché
     */
    public static function getTributes(): array
    {
        return Cache::remember('tributes_all', 3600, function () {
            return Tribute::all()->keyBy('id')->toArray();
        });
    }

    /**
     * Obtener tasa de IVA con caché
     */
    public static function getIvaRate(): float
    {
        $taxes = Cache::remember('tax_rates_iva_isr', 3600, function () {
            return Tribute::whereIn('id', [1, 3])->pluck('rate', 'id')->toArray();
        });
        return ($taxes[1] ?? 0) / 100;
    }

    /**
     * Obtener tasa de ISR con caché
     */
    public static function getIsrRate(): float
    {
        $taxes = Cache::remember('tax_rates_iva_isr', 3600, function () {
            return Tribute::whereIn('id', [1, 3])->pluck('rate', 'id')->toArray();
        });
        return ($taxes[3] ?? 0) / 100;
    }

    /**
     * Limpiar todas las cachés del sistema
     */
    public static function clearAll(): void
    {
        Cache::forget('company_config');
        Cache::forget('tributes_all');
        Cache::forget('tax_rates_iva_isr');
        Cache::forget('tribute_iva_rate');
    }

    /**
     * Refrescar caché de empresa
     */
    public static function refreshCompany(): ?Company
    {
        Cache::forget('company_config');
        return self::getCompany();
    }

    /**
     * Refrescar caché de tributos
     */
    public static function refreshTributes(): array
    {
        Cache::forget('tributes_all');
        Cache::forget('tax_rates_iva_isr');
        Cache::forget('tribute_iva_rate');
        return self::getTributes();
    }
}
