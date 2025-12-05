<?php

namespace Rapidez\StatamicQuote\Listeners;

use Rapidez\Core\Facades\Rapidez;
use Rapidez\StatamicQuote\Jobs\SendQuoteJob;
use Statamic\Events\FormSubmitted;
use TorMorten\Eventy\Facades\Eventy;

class QuoteRequestListener
{
    public function handle(FormSubmitted $event)
    {
        if ($event->submission->form()->handle() !== 'quote_form') {
            return;
        }

        $event->submission->set('store_code', config('rapidez.store_code'));

        if (! config('rapidez.quote.auto_send_quote', true)) {
            return;
        }

        $quoteData = Eventy::filter('quote.data', [
            'store' => Rapidez::getStore(config('rapidez.store')),
            'products' => $event->submission->augmentedValue('products')->value(),
            'formData' => $event->submission->toArray(),
        ]);

        if (!$quoteData) {
            return;
        }

        SendQuoteJob::dispatch($quoteData);
    }
}
