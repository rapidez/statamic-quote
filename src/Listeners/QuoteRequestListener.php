<?php

namespace Rapidez\Quote\Listeners;

use Rapidez\Core\Facades\Rapidez;
use Rapidez\Quote\Jobs\SendQuoteJob;
use Statamic\Events\FormSubmitted;

class QuoteRequestListener
{
    public function handle(FormSubmitted $event)
    {
        if ($event->submission->form()->handle() !== 'quote_form') {
            return;
        }

        $products = $event->submission->augmentedValue('products')->value();

        SendQuoteJob::dispatch([
            'store' => Rapidez::getStore(config('rapidez.store')),
            'products' => $products,
            'formData' => $event->submission->toArray(),
        ]);
    }
}