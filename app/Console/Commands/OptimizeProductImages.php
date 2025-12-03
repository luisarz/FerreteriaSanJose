<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;

class OptimizeProductImages extends Command
{
    protected $signature = 'app:optimize-images
                            {--path=products : Carpeta de imágenes a optimizar}
                            {--width=800 : Ancho máximo}
                            {--height=800 : Alto máximo}
                            {--quality=80 : Calidad de compresión (1-100)}
                            {--webp : Convertir a WebP}
                            {--backup : Crear backup antes de optimizar}';

    protected $description = 'Optimiza las imágenes existentes (redimensiona y comprime)';

    public function handle()
    {
        $path = $this->option('path');
        $maxWidth = (int) $this->option('width');
        $maxHeight = (int) $this->option('height');
        $quality = (int) $this->option('quality');
        $convertToWebp = $this->option('webp');
        $createBackup = $this->option('backup');

        $storagePath = storage_path("app/public/{$path}");

        if (!is_dir($storagePath)) {
            $this->error("La carpeta {$storagePath} no existe.");
            return Command::FAILURE;
        }

        // Obtener todas las imágenes
        $files = glob($storagePath . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        $totalFiles = count($files);

        if ($totalFiles === 0) {
            $this->info('No se encontraron imágenes para optimizar.');
            return Command::SUCCESS;
        }

        $this->info("Encontradas {$totalFiles} imágenes en {$path}");

        // Crear backup si se solicitó
        if ($createBackup) {
            $backupPath = storage_path("app/public/{$path}_backup_" . date('Y-m-d_His'));
            $this->info("Creando backup en: {$backupPath}");

            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            foreach ($files as $file) {
                copy($file, $backupPath . '/' . basename($file));
            }
            $this->info('Backup creado exitosamente.');
        }

        $bar = $this->output->createProgressBar($totalFiles);
        $bar->start();

        $optimized = 0;
        $totalSavedBytes = 0;
        $errors = [];

        foreach ($files as $file) {
            try {
                $originalSize = filesize($file);

                // Cargar imagen
                $image = Image::read($file);

                // Obtener dimensiones originales
                $width = $image->width();
                $height = $image->height();

                // Redimensionar solo si excede el tamaño máximo
                if ($width > $maxWidth || $height > $maxHeight) {
                    $image->scaleDown($maxWidth, $maxHeight);
                }

                // Determinar formato de salida
                if ($convertToWebp) {
                    $newPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $file);
                    $image->encode(new WebpEncoder($quality))->save($newPath);

                    // Eliminar archivo original si es diferente
                    if ($newPath !== $file && file_exists($newPath)) {
                        unlink($file);
                    }
                    $file = $newPath;
                } else {
                    // Guardar en formato original con compresión
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                    if (in_array($extension, ['jpg', 'jpeg'])) {
                        $image->encode(new JpegEncoder($quality))->save($file);
                    } else {
                        $image->save($file);
                    }
                }

                $newSize = filesize($file);
                $savedBytes = $originalSize - $newSize;
                $totalSavedBytes += $savedBytes;
                $optimized++;

            } catch (\Exception $e) {
                $errors[] = basename($file) . ': ' . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $savedMB = round($totalSavedBytes / 1024 / 1024, 2);
        $this->info("Imágenes optimizadas: {$optimized}/{$totalFiles}");
        $this->info("Espacio ahorrado: {$savedMB} MB");

        if (count($errors) > 0) {
            $this->newLine();
            $this->warn('Errores encontrados:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return Command::SUCCESS;
    }
}
