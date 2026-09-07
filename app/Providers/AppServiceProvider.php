<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require_once app_path('Helpers/settings.php');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Prevención de error de longitud de clave en MySQL/MariaDB de hosting compartido
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        // Configurar paginación Bootstrap 5 para evitar iconos SVG gigantes de Tailwind
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // Forzar HTTPS en producción o cuando se sirve con SSL en cPanel
        if (config('app.env') === 'production' || request()->server('HTTP_X_FORWARDED_PROTO') === 'https' || request()->isSecure()) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
