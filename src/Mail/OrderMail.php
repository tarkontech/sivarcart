<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Tarkontech\SivarCart\Enums\OrderNotification;
use Tarkontech\SivarCart\Models\Order;

/**
 * The base of the emails the buyer receives.
 *
 * Every mail of the buyer writes about one order, so they share the
 * recipient, the sender and the subject. What tells them apart is the
 * notification they belong to and the template they render.
 *
 * The order and the recipient are protected on purpose: Laravel hands the
 * public properties of a mailable to the template, and that would overwrite
 * the data the mail prepares for it.
 */
abstract class OrderMail extends Mailable
{
    protected readonly Order $order;

    protected readonly string $recipient;

    public function __construct(Order $order, string $recipient)
    {
        $this->order = $order;
        $this->recipient = $recipient;
    }

    /**
     * The notification this mail belongs to.
     */
    abstract protected function notification(): OrderNotification;

    /**
     * The markdown template this mail renders.
     */
    abstract protected function template(): string;

    /**
     * The data the template needs besides the order.
     *
     * @return array<string, mixed>
     */
    protected function templateData(): array
    {
        return [];
    }

    /**
     * The subject of the email.
     */
    protected function mailSubject(): string
    {
        return (string) __('sivarcart::messages.order_notifications.'.$this->notification()->value.'.subject', [
            'number' => $this->order->order_number,
        ]);
    }

    /**
     * The address the email comes from.
     *
     * When the configuration does not name one, the address of the
     * "mail.from" configuration of the application is used.
     */
    protected function sender(): ?Address
    {
        $from = config('sivarcart.notifications.from');

        if (! is_string($from) || trim($from) === '') {
            return null;
        }

        return new Address(trim($from));
    }

    /**
     * An amount written the way the buyer reads it.
     */
    protected function money(float $amount): string
    {
        return number_format($amount, 2).' '.$this->order->currency;
    }

    /**
     * Build the envelope of the email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->sender(),
            to: [new Address($this->recipient)],
            subject: $this->mailSubject(),
        );
    }

    /**
     * Build the content of the email.
     */
    public function content(): Content
    {
        /** @var view-string $template */
        $template = $this->template();

        return new Content(
            markdown: $template,
            with: array_merge([
                'order' => $this->order->loadMissing('items'),
            ], $this->templateData()),
        );
    }
}
