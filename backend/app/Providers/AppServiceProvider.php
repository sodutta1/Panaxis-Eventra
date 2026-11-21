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
            'client' => '[0-9]{1,18}',
            'event' => '[0-9]{1,18}',
            'expense' => '[0-9]{1,18}',
            'guest' => '[0-9]{1,18}',
            'invoice' => '[0-9]{1,18}',
            'item' => '[0-9]{1,18}',
            'lead' => '[0-9]{1,18}',
            'quotation' => '[0-9]{1,18}',
            'task' => '[0-9]{1,18}',
            'ticket' => '[0-9]{1,18}',
            'user' => '[0-9]{1,18}',
            'vendor' => '[0-9]{1,18}',
            'venue' => '[0-9]{1,18}',
            'workOrder' => '[0-9]{1,18}',
        ]);
    }
}
