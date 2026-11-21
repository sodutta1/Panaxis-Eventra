<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
        // Route model IDs are always integers; reject non-numeric parameters
        // with 404 during route matching instead of a 500 TypeError downstream.
        Route::patterns([
            'client' => '[0-9]+',
            'event' => '[0-9]+',
            'expense' => '[0-9]+',
            'guest' => '[0-9]+',
            'invoice' => '[0-9]+',
            'item' => '[0-9]+',
            'lead' => '[0-9]+',
            'quotation' => '[0-9]+',
            'task' => '[0-9]+',
            'ticket' => '[0-9]+',
            'user' => '[0-9]+',
            'vendor' => '[0-9]+',
            'venue' => '[0-9]+',
            'workOrder' => '[0-9]+',
        ]);
    }
}
