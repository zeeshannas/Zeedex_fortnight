<?php

namespace GroceryCrud;

use Illuminate\Support\ServiceProvider;

class LaravelAssetsServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap public assets for Grocery CRUD.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../public/vendor/grocery-crud' => public_path('vendor/grocery-crud'),
        ], 'public');
    }
}
