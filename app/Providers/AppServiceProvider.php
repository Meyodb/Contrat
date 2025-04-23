<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Définir le singleton files
        $this->app->singleton('files', function ($app) {
            return new Filesystem;
        });
        
        // Définir encrypter si nécessaire
        if (!$this->app->bound('encrypter')) {
            $this->app->singleton('encrypter', function ($app) {
                $config = $app->make('config')->get('app');
                
                $key = $config['key'];
                $cipher = $config['cipher'];
                
                return new Encrypter(base64_decode(substr($key, 7)), $cipher);
            });
        }
        
        // Définir le service DB si nécessaire
        if (!$this->app->bound('db')) {
            $this->app->singleton('db', function ($app) {
                return $app->make('db.factory')->make($app->make('config')->get('database.connections'), $app->make('config')->get('database.default'));
            });
        }
        
        // Définir le service Hash si nécessaire
        if (!$this->app->bound('hash')) {
            $this->app->singleton('hash', function ($app) {
                return new \Illuminate\Hashing\HashManager($app);
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Définir la longueur par défaut pour les chaînes dans les migrations
        Schema::defaultStringLength(191);
    }
}
