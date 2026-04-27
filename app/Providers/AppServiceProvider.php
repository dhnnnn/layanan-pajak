<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Izinkan semua orang mengakses API docs (termasuk production)
        Gate::define('viewApiDocs', function () {
            return true;
        });

        // Hanya tampilkan route /api/v1/* di docs, exclude /api/user
        Scramble::routes(function (Route $route) {
            return Str::startsWith($route->uri, 'api/v1/');
        });

        // Tambah Bearer Token security scheme
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
                    ->setDescription('Masukkan token API.')
            );
        });
    }
}
