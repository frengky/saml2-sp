<?php

namespace Frengky\Saml2;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

use OneLogin\Saml2\Auth;

class Saml2AuthServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            realpath(__DIR__.'/../config/saml2sp.php') => config_path('saml2sp.php')
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            realpath(__DIR__.'/../config/saml2sp.php'), 'saml2sp'
        );

        $this->app->singleton(Auth::class, function ($app) {
            
            config([
                'saml2sp.sp.entityId' => route('saml2sp.metadata'),
                'saml2sp.sp.assertionConsumerService.url' => route('saml2sp.acs'),
                'saml2sp.sp.singleLogoutService.url' => route('saml2sp.sls')
            ]);

            $settings = config('saml2sp');
            return new Auth($settings);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Auth::class];
    }
}