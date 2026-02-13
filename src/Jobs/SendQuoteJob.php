<?php

namespace Rapidez\StatamicQuote\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\PendingMail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Rapidez\Core\Facades\Rapidez;
use Rapidez\StatamicQuote\Mail\Quote;
use TorMorten\Eventy\Facades\Eventy;

class SendQuoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(protected array $quoteData) { }

    public function handle() {
        $email = $this->quoteData['formData']['email'] ?? null;
        if (! $email) {
            return;
        }

        Rapidez::setStore($this->quoteData['store']);

        $pdf = Pdf::loadView('rapidez-quote::exports.quote', $this->quoteData)
            ->setOption('fontDir', resource_path('/css/fonts'));

        /** @var PendingMail $mail */
        $mail = Eventy::filter('quote.mail', Mail::to($email), $this->quoteData);
        $mail->send(new Quote($pdf, $this->quoteData));
    }
}
