<?php

namespace Rapidez\StatamicQuote;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Rapidez\StatamicQuote\Fieldtypes\Products;
use Rapidez\StatamicQuote\Listeners\QuoteRequestListener;
use Statamic\Events\FormSubmitted;
use Statamic\Statamic;

class QuoteServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerConfigs();
    }

    protected function registerConfigs(): self
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/rapidez/quote.php', 'rapidez.quote');

        return $this;
    }

    public function boot()
    {
        $this
            ->bootFieldtypes()
            ->bootListeners()
            ->bootPublishables()
            ->bootPublishAfterInstall()
            ->bootRoutes()
            ->bootTranslations()
            ->bootViews()
            ->bootVite();
    }

    protected function bootFieldtypes(): static
    {
        Products::register();

        return $this;
    }

    protected function bootListeners(): static
    {
        Event::listen(FormSubmitted::class, QuoteRequestListener::class);

        return $this;
    }

    protected function bootPublishables(): static
    {
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('vendor/rapidez-quote'),
        ], 'quote-views');

        $this->publishes([
            __DIR__.'/../resources/blueprints/forms' => resource_path('blueprints/forms'),
            __DIR__.'/../resources/views/form_fields' => resource_path('views/form_fields'),
        ], 'quote-content');

        $this->publishes([
            __DIR__.'/../config/rapidez/quote.php' => config_path('rapidez/quote.php'),
        ], 'quote-config');

        $this->publishes([
            __DIR__ . '/../resources/dist' => public_path('vendor/rapidez-quote'),
        ], 'quote-dist');

        return $this;
    }

    protected function bootPublishAfterInstall(): static
    {
        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish', [
                '--tag' => 'quote-dist',
                '--force' => true,
            ]);
        });

        return $this;
    }

    protected function bootRoutes(): static
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        return $this;
    }

    protected function bootTranslations(): static
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../resources/lang');

        return $this;
    }

    protected function bootViews(): static
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rapidez-quote');

        return $this;
    }

    protected function bootVite(): static
    {
        Statamic::vite('rapidez-quote', [
            'buildDirectory' => 'vendor/rapidez-quote/build',
            'input' => [
                'resources/js/cp.js',
                'resources/css/cp.css',
            ],
        ]);

        return $this;
    }
}