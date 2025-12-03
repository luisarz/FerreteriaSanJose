<?php

namespace App\Console\Commands;

use App\Models\HistoryDte;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupDTEFiles extends Command
{
    protected $signature = 'app:cleanup-dte-files
                            {--dry-run : Solo mostrar qué archivos se eliminarían sin eliminarlos}
                            {--json-only : Solo eliminar archivos JSON}
                            {--pdf-only : Solo eliminar archivos PDF}
                            {--verify : Verificar que existe en BD antes de eliminar}';

    protected $description = 'Elimina archivos DTE físicos (JSON/PDF) ya que están almacenados en la base de datos';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $jsonOnly = $this->option('json-only');
        $pdfOnly = $this->option('pdf-only');
        $verify = $this->option('verify');

        $dtePath = storage_path('app/public/DTEs');

        if (!is_dir($dtePath)) {
            $this->info('No se encontró el directorio de DTEs.');
            return Command::SUCCESS;
        }

        $this->info($dryRun ? '🔍 Modo simulación (dry-run) - No se eliminarán archivos' : '🗑️ Eliminando archivos DTE...');
        $this->newLine();

        $deletedJson = 0;
        $deletedPdf = 0;
        $skipped = 0;
        $errors = [];
        $freedBytes = 0;

        // Obtener todos los archivos
        $extensions = [];
        if (!$pdfOnly) $extensions[] = 'json';
        if (!$jsonOnly) $extensions[] = 'pdf';

        if (empty($extensions)) {
            $extensions = ['json', 'pdf'];
        }

        $files = [];
        foreach ($extensions as $ext) {
            $pattern = $dtePath . '/*.' . $ext;
            $files = array_merge($files, glob($pattern) ?: []);
        }

        $totalFiles = count($files);

        if ($totalFiles === 0) {
            $this->info('No se encontraron archivos DTE para limpiar.');
            return Command::SUCCESS;
        }

        $this->info("Encontrados {$totalFiles} archivos DTE");

        // Cargar códigos de generación de BD si verificamos
        $dbCodes = [];
        if ($verify) {
            $this->info('Cargando códigos de generación desde la base de datos...');
            $dbCodes = HistoryDte::whereNotNull('codigoGeneracion')
                ->pluck('codigoGeneracion')
                ->flip()
                ->toArray();
            $this->info('Encontrados ' . count($dbCodes) . ' DTEs en base de datos');
        }

        $bar = $this->output->createProgressBar($totalFiles);
        $bar->start();

        foreach ($files as $file) {
            $filename = basename($file);
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $codigoGeneracion = pathinfo($filename, PATHINFO_FILENAME);

            // Verificar si existe en BD
            if ($verify && !isset($dbCodes[$codigoGeneracion])) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $fileSize = filesize($file);

            if (!$dryRun) {
                try {
                    if (@unlink($file)) {
                        $freedBytes += $fileSize;
                        if ($extension === 'json') {
                            $deletedJson++;
                        } else {
                            $deletedPdf++;
                        }
                    } else {
                        $errors[] = "No se pudo eliminar: {$filename}";
                    }
                } catch (\Exception $e) {
                    $errors[] = "{$filename}: {$e->getMessage()}";
                }
            } else {
                $freedBytes += $fileSize;
                if ($extension === 'json') {
                    $deletedJson++;
                } else {
                    $deletedPdf++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $freedMB = round($freedBytes / 1024 / 1024, 2);
        $action = $dryRun ? 'Se eliminarían' : 'Eliminados';

        $this->info("📊 Resumen:");
        $this->table(
            ['Tipo', 'Cantidad'],
            [
                ['Archivos JSON ' . ($dryRun ? '(simulado)' : 'eliminados'), $deletedJson],
                ['Archivos PDF ' . ($dryRun ? '(simulado)' : 'eliminados'), $deletedPdf],
                ['Archivos omitidos (no en BD)', $skipped],
                ['Espacio ' . ($dryRun ? 'que se liberaría' : 'liberado'), "{$freedMB} MB"],
            ]
        );

        if (count($errors) > 0) {
            $this->newLine();
            $this->warn('⚠️ Errores encontrados:');
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->error("  - {$error}");
            }
            if (count($errors) > 10) {
                $this->warn('  ... y ' . (count($errors) - 10) . ' errores más');
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('💡 Ejecuta sin --dry-run para eliminar los archivos realmente');
        }

        return Command::SUCCESS;
    }
}
