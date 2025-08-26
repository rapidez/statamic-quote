<?php

namespace Rapidez\Quote\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Rapidez\Core\Facades\Rapidez;
use Rapidez\Quote\Mail\Quote;

class SendQuoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(protected array $quoteData) { }

    public function handle() {
        Rapidez::setStore($this->quoteData['store']);

        $pdf = Pdf::loadView('rapidez-quote::exports.quote', $this->quoteData)
            ->setOption('fontDir', resource_path('/css/fonts'));

        Mail::to('jade@justbetter.nl')->send(new Quote($pdf));
    }
}