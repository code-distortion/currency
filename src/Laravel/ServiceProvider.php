<?php

namespace CodeDistortion\Currency\Laravel;

use CodeDistortion\Currency\Currency;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Events\EventDispatcher;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Throwable;

/**
 * Currency ServiceProvider for Laravel
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Service-provider register method
     *
     * @return void
     */
    public function register(): void
    {
        // Needed for Laravel < 5.3 compatibility
    }

    /**
     * Service-provider boot method
     *
     * @return void
     */
    public function boot(): void
    {
        $this->initialiseConfig();
        $this->setDefaults();
        $this->localeListen();
    }



    /**
     * Initialise the config settings/file
     *
     * @return void
     */
    protected function initialiseConfig(): void
    {
        // initialise the config
        $configPath = __DIR__.'/../../config/config.php';
        $this->mergeConfigFrom($configPath, 'currency');

        // allow the default config to be published
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [$configPath => config_path('currency.php'),],
                'config'
            );
        }
    }

    /**
     * Set the Currency default values
     *
     * @return void
     */
    protected function setDefaults(): void
    {
        $this->updateLocale();

        Currency::setDefaultImmutability(config('currency.immutable'));
        Currency::setDefaultFormatSettings(config('currency.format_settings'));
    }

    /**
     * Listen for locale changes
     *
     * @return void
     */
    protected function localeListen(): void
    {
        if (!$this->app->bound('events')) {
            return;
        }

        $events = $this->app['events'];
        if ($this->isEventDispatcher($events)) {

            // update the locale when the locale-updated event is triggered
            $event = class_exists('Illuminate\Foundation\Events\LocaleUpdated')
                    ? 'Illuminate\Foundation\Events\LocaleUpdated'
                    : 'locale.changed';
            $service = $this;
            $events->listen($event, function () use ($service) {
                $service->updateLocale();
            });
        }
    }

    /**
     * Ensure the given thing is an event dispatcher
     *
     * @param mixed $instance The object to check.
     * @return boolean
     */
    protected function isEventDispatcher($instance)
    {
        return ($instance instanceof EventDispatcher
            || $instance instanceof Dispatcher
            || $instance instanceof DispatcherContract);
    }

    /**
     * Update the Currency locale
     *
     * @return void
     */
    protected function updateLocale(): void
    {
        $app = (($this->app) && (method_exists($this->app, 'getLocale')) ? $this->app : app('translator'));
        Currency::setDefaultLocale($app->getLocale());
    }
}
