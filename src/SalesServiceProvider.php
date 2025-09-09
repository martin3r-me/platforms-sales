<?php

namespace Platform\Sales;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;

// Optional: Models und Policies absichern
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardTemplate;
use Platform\Sales\Policies\SalesDealPolicy;
use Platform\Sales\Policies\SalesBoardPolicy;
use Platform\Sales\Policies\SalesBoardTemplatePolicy;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Commands registrieren
        if ($this->app->runningInConsole()) {
            // Commands für Sales-Modul (falls benötigt)
            // $this->commands([
            //     \Platform\Sales\Console\Commands\CheckDealEscalationsCommand::class,
            // ]);
        }
    }

    public function boot(): void
    {
        // Modul-Registrierung nur, wenn Config & Tabelle vorhanden
        if (
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'sales',
                'title'      => 'Vertriebsboard',
                'routing'    => config('sales.routing'),
                'guard'      => config('sales.guard'),
                'navigation' => config('sales.navigation'),
                'sidebar'    => config('sales.sidebar'),
                'billables'  => config('sales.billables'),
            ]);
        }

        // Routen nur laden, wenn das Modul registriert wurde
        if (PlatformCore::getModule('sales')) {
            ModuleRouter::group('sales', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/guest.php');
            }, requireAuth: false);

            ModuleRouter::group('sales', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Config veröffentlichen & zusammenführen
        $this->publishes([
            __DIR__.'/../config/sales.php' => config_path('sales.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/sales.php', 'sales');

        // Migrations, Views, Livewire-Komponenten
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sales');
        $this->registerLivewireComponents();

        // Policies nur registrieren, wenn Klassen vorhanden sind
        if (class_exists(SalesDeal::class) && class_exists(SalesDealPolicy::class)) {
            Gate::policy(SalesDeal::class, SalesDealPolicy::class);
        }

        if (class_exists(SalesBoard::class) && class_exists(SalesBoardPolicy::class)) {
            Gate::policy(SalesBoard::class, SalesBoardPolicy::class);
        }

        if (class_exists(SalesBoardTemplate::class) && class_exists(SalesBoardTemplatePolicy::class)) {
            Gate::policy(SalesBoardTemplate::class, SalesBoardTemplatePolicy::class);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Sales\\Livewire';
        $prefix = 'sales';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            // sales.deal.index aus sales + deal/index.php
            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}