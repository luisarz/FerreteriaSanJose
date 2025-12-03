<?php

namespace App\Services;

use App\Models\Company;
use App\Models\DocumentType;
use App\Models\PaymentMethod;
use App\Models\Tribute;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

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

    /**
     * Obtener tipos de documento con caché (para selects)
     */
    public static function getDocumentTypes(): array
    {
        return Cache::remember('document_types_all', 86400, function () {
            return DocumentType::where('is_active', true)
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Obtener métodos de pago con caché (para selects)
     */
    public static function getPaymentMethods(): array
    {
        return Cache::remember('payment_methods_all', 86400, function () {
            return PaymentMethod::where('is_active', true)
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Limpiar caché de permisos (Spatie Permission)
     */
    public static function clearPermissionsCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Refrescar caché de catálogos
     */
    public static function refreshCatalogs(): void
    {
        Cache::forget('document_types_all');
        Cache::forget('payment_methods_all');
        self::getDocumentTypes();
        self::getPaymentMethods();
    }

    /**
     * Limpiar todas las cachés incluyendo permisos
     */
    public static function clearAllWithPermissions(): void
    {
        self::clearAll();
        self::clearPermissionsCache();
        Cache::forget('document_types_all');
        Cache::forget('payment_methods_all');
    }
}
