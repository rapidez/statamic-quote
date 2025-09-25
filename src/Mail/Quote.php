<?php

namespace Rapidez\StatamicQuote\Mail;

use Barryvdh\DomPDF\PDF;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Quote extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailSubject;

    /**
     * Create a new message instance.
     */
    public function __construct(public PDF $pdf, public array $quoteData)
    {
        $this->mailSubject = __('Quote') . ' - ' . now()->toDateTimeString();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        if (config('rapidez.quote.email_markdown')) {
            return new Content(
                markdown: 'rapidez-quote::mail.quote',
            );
        } else {
            return new Content(
                view: 'rapidez-quote::mail.quote',
            );
        }
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => $this->pdf->output(), $this->mailSubject)->withMime('application/pdf')
        ];
    }
}
