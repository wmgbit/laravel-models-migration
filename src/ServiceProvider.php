<?php
namespace WMG\Migration;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider {

    public function boot(\Illuminate\Routing\Router $router) {
        $this->commands([
            \WMG\Migration\Commands\Migrate ::class,
        ]);
    }
}