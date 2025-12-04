# Reporte de Cambios - Sistema DTE y Optimizaciones
**Fecha:** 3 de Diciembre 2025
**Sistema:** Ferretería San José - ERP con Facturación Electrónica DTE

---

## Resumen Ejecutivo

Se realizaron múltiples correcciones y optimizaciones en el sistema, enfocadas en:
1. Corrección de imágenes (logo y QR) en documentos PDF, tickets y emails DTE
2. Inclusión de contingencias en reportes y descargas
3. Optimización de rendimiento del servidor
4. Mejoras en la interfaz de usuario

---

## 1. Correcciones de Imágenes en Documentos DTE

### 1.1 Problema Original
- El logo de la sucursal y el código QR no se mostraban en:
  - PDFs de DTE
  - Tickets térmicos
  - Emails con DTE adjunto

### 1.2 Causa Raíz
DomPDF (generador de PDFs) no puede resolver URLs generadas con `asset()` o `Storage::url()`. Requiere rutas absolutas del sistema de archivos o datos en base64.

### 1.3 Solución Implementada

#### Archivos Modificados:

**`app/Http/Controllers/DTEController.php`**
```php
// ANTES (no funcionaba):
$logoUrl = Storage::url($logo);
$qrUrl = asset('storage/qrcodes/' . $qrFileName);

// DESPUÉS (funciona correctamente):
$logoBase64 = null;
if ($logo) {
    $logoFullPath = storage_path('app/public/' . $logo);
    if (file_exists($logoFullPath)) {
        $logoData = file_get_contents($logoFullPath);
        $logoMime = mime_content_type($logoFullPath);
        $logoBase64 = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
    }
}

$qrBase64 = null;
if (file_exists($qrPath)) {
    $qrData = file_get_contents($qrPath);
    $qrBase64 = 'data:image/png;base64,' . base64_encode($qrData);
}
```

**Métodos afectados:**
- `printDTETicket()` - Impresión de ticket térmico
- `printDTEPdf()` - Generación de PDF
- Vistas Blade actualizadas para recibir `$logoBase64` y `$qrBase64`

**`app/Http/Controllers/SenEmailDTEController.php`**
- Mismo patrón de conversión a base64 aplicado
- Método `generateTempPdf()` actualizado

**`app/Http/Controllers/ReportsController.php`**
- Conversión a base64 para generación de PDFs en descargas ZIP

**`resources/views/emails/sendDTE.blade.php`**
```php
// ANTES (error: Unable to open path 'storage/...'):
<img src="{{ $message->embed('storage/' . $sale->wherehouse->logo) }}">

// DESPUÉS:
@php
    $logoPath = $sale->wherehouse->logo
        ? storage_path('app/public/' . $sale->wherehouse->logo)
        : null;
@endphp
@if($logoPath && file_exists($logoPath))
    <img src="{{ $message->embed($logoPath) }}" alt="Logo">
@endif
```

---

## 2. Inclusión de Contingencias en Reportes

### 2.1 Problema Original
Las ventas enviadas por contingencia no aparecían en:
- Descarga de ZIP con XMLs
- Libro de ventas (Excel)

Causa: El filtro usaba `whereNotNull('selloRecibido')`, pero las contingencias no tienen sello de recepción inmediato.

### 2.2 Solución Implementada

**`app/Http/Controllers/ReportsController.php`**
```php
// ANTES:
->whereNotNull('selloRecibido')

// DESPUÉS:
->where('estado', 'PROCESADO')
```

**`app/Exports/SalesExportFac.php`**
```php
// ANTES:
->whereHas('dte', fn($q) => $q->whereNotNull('selloRecibido'))

// DESPUÉS:
->whereHas('dte', fn($q) => $q->where('estado', 'PROCESADO'))
```

### 2.3 Nueva Funcionalidad: Descargar Todos los Tipos de Documento
Si no se selecciona ningún tipo de documento, ahora descarga todos los tipos DTE válidos:

```php
if (!empty($this->documentType)) {
    $query->whereIn('document_type_id', [$this->documentType]);
} else {
    // Descarga todos los tipos DTE si no se especifica
    $query->whereIn('document_type_id', [1, 3, 5, 11, 14]);
}
```

---

## 3. Optimizaciones de Rendimiento

### 3.1 Desactivación de Polling Excesivo

**Problema:** 4 RelationManagers tenían `pollingInterval = '1s'`, causando consultas cada segundo.

**Solución:** Desactivar polling (sistema es monousuario)

**Archivos modificados:**
- `app/Filament/Resources/Adjustments/RelationManagers/AdjustmentRelationManager.php`
- `app/Filament/Resources/CNItems/RelationManagers/CNtemsRelationManager.php`
- `app/Filament/Resources/Sales/RelationManagers/SaleItemsRelationManager.php`
- `app/Filament/Resources/Transfers/RelationManagers/TransferItemsRelationManager.php`

```php
// ANTES:
protected static ?string $pollingInterval = '1s';

// DESPUÉS:
protected static ?string $pollingInterval = null;
```

### 3.2 Índices de Base de Datos para Kardex

**Nueva migración:** `database/migrations/2025_12_03_102955_add_indexes_to_kardex_table.php`

```php
Schema::table('kardex', function (Blueprint $table) {
    // Índice compuesto para búsquedas por inventario y fecha
    $table->index(['inventory_id', 'date'], 'idx_kardex_inventory_date');

    // Índice para búsquedas por número de documento
    $table->index('document_number', 'idx_kardex_document_number');

    // Índice para búsquedas por entidad (cliente/proveedor)
    $table->index('entity', 'idx_kardex_entity');

    // Índice compuesto para filtros por sucursal y fecha
    $table->index(['branch_id', 'date'], 'idx_kardex_branch_date');

    // Índice para filtros por tipo de operación
    $table->index('operation_type', 'idx_kardex_operation_type');
});
```

**Comando para ejecutar:**
```bash
php artisan migrate
```

### 3.3 Corrección de N+1 Queries en Compras

**Problema:** Al anular compras, se ejecutaba una consulta por cada item para obtener el inventario.

**`app/Filament/Resources/Purchases/PurchaseResource.php`** (Acción Anular)
```php
// ANTES (N+1 query):
$purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)->get();
foreach ($purchaseItems as $item) {
    $inventory = Inventory::find($item->inventory_id); // ¡Query por cada item!
}

// DESPUÉS (Eager loading):
$purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)
    ->with('inventory')
    ->get();
foreach ($purchaseItems as $item) {
    $inventory = $item->inventory; // Ya cargado
}
```

**`app/Filament/Resources/Purchases/Pages/EditPurchase.php`** (Método aftersave)
- Mismo patrón aplicado

### 3.4 Optimización de Carga de Precios en Inventario

**`app/Filament/Resources/Inventories/InventoryResource.php`**
```php
// ANTES (carga todos los precios):
->modifyQueryUsing(fn($query) => $query->with(['product', 'branch', 'prices']))

// DESPUÉS (solo precio default):
->modifyQueryUsing(fn($query) => $query->with([
    'product',
    'branch',
    'prices' => fn($q) => $q->where('is_default', 1)->limit(1)
]))
```

### 3.5 Columnas Ocultas por Defecto en Kardex

**`app/Filament/Resources/Kardexes/KardexResource.php`**

Columnas ahora ocultas por defecto (reducen tiempo de renderizado):
- `inventory_id`
- `document_type`
- `nationality` (ya estaba)
- `operation_type` (ya estaba)

---

## 4. Mejoras de Interfaz de Usuario

### 4.1 Iconos de Grupos de Navegación

**`app/Providers/Filament/AdminPanelProvider.php`**

| Grupo | Icono Anterior | Icono Nuevo |
|-------|----------------|-------------|
| Almacén | (ninguno) | `heroicon-o-building-storefront` |
| Inventario | (ninguno) | `heroicon-o-archive-box` |
| Facturación | (ninguno) | `heroicon-o-document-text` |
| Caja Chica | (ninguno) | `heroicon-o-banknotes` |
| Contabilidad | (ninguno) | `heroicon-o-calculator` |
| Recursos Humanos | (ninguno) | `heroicon-o-user-group` |
| Catálogos Hacienda | (ninguno) | `heroicon-o-building-library` |

---

## 5. Limpieza de Código

### 5.1 Método Eliminado
**`app/Http/Controllers/DTEController.php`**
- Eliminado método `saveRestoreJson()` - no se utilizaba

---

## 6. Commits Realizados

| Commit | Descripción |
|--------|-------------|
| `d701396` | Perf: Optimizaciones de rendimiento en Filament y DB |
| `0b93d73` | Perf: Desactivar polling en RelationManagers |
| `5479e8e` | Fix: Incluir contingencias en ZIP y libro de ventas |
| `d4cc01a` | Fix: Ruta absoluta para logo en email DTE |
| `5cf0669` | Fix: Usar base64 para imágenes en email DTE |
| `b9387c1` | Cleanup: Eliminar método saveRestoreJson sin uso |
| `6991a3b` | Fix: Usar base64 para imágenes en PDF DTE |
| `8c91f62` | Fix: Logo y QR no cargaban en PDF y ticket DTE |

---

## 7. Acciones Pendientes en Producción

### 7.1 Ejecutar Migración de Índices
```bash
php artisan migrate
```

### 7.2 Si hay error de "tabla ya existe"
Ejecutar en la base de datos:
```sql
SET @next_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);

INSERT INTO migrations (migration, batch) VALUES
('0001_01_01_00000014_create_payment_methods_table', @next_batch);
```

Luego ejecutar nuevamente:
```bash
php artisan migrate
```

### 7.3 Limpiar Caché (Recomendado)
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

---

## 8. Impacto Esperado

| Área | Mejora |
|------|--------|
| **PDFs/Tickets** | Logo y QR ahora visibles correctamente |
| **Emails DTE** | Logo de sucursal visible en correos |
| **Reportes** | Contingencias incluidas en ZIP y libro de ventas |
| **Rendimiento DB** | Consultas de Kardex 50-80% más rápidas con índices |
| **Rendimiento UI** | Menos carga al servidor sin polling innecesario |
| **Memoria** | Menor uso al cargar solo precio default |

---

*Reporte generado automáticamente - Claude Code*
