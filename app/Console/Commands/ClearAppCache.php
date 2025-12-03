<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class ClearAppCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-clear {--permissions : También limpiar caché de permisos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia la caché de la aplicación (empresa, tributos, catálogos)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Limpiando caché de la aplicación...');

        if ($this->option('permissions')) {
            CacheService::clearAllWithPermissions();
            $this->info('✓ Caché de permisos limpiada');
        } else {
            CacheService::clearAll();
        }

        $this->info('✓ Caché de empresa limpiada');
        $this->info('✓ Caché de tributos limpiada');
        $this->info('✓ Caché de catálogos limpiada');

        $this->newLine();
        $this->info('¡Caché limpiada exitosamente!');

        return Command::SUCCESS;
    }
}
