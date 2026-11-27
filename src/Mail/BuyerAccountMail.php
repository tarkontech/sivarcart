<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Tarkontech\SivarCart\Models\Customer;

/**
 * The base of the emails the account of a buyer receives.
 *
 * Every mail of the account writes about the same buyer, so they share the
 * recipient, the sender and the subject. What tells them apart is the
 * template they render.
 *
 * The customer and the recipient are protected on purpose: Laravel hands the
 * public properties of a mailable to the template, and that would overwrite
 * the data the mail prepares for it.
 */
abstract class BuyerAccountMail extends Mailable
{
    protected readonly Customer $customer;

    /**
     * The customer this mail writes about.
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * The markdown template this mail renders.
     */
    abstract protected function template(): string;

    /**
     * The key of the texts of this mail.
     */
    abstract protected function subjectKey(): string;

    /**
     * The data the template needs besides the customer.
     *
     * @return array<string, mixed>
     */
    protected function templateData(): array
    {
        return [
            'store' => (string) config('sivarcart.store.name', 'SivarCart'),
        ];
    }

    /**
     * The subject of the email.
     */
    protected function mailSubject(): string
    {
        return (string) __('sivarcart::messages.account_notifications.'.$this->subjectKey().'.subject', [
            'store' => (string) config('sivarcart.store.name', 'SivarCart'),
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
     * The address the email goes to.
     */
    protected function recipient(): Address
    {
        return new Address((string) $this->customer->email);
    }

    /**
     * The link that points at a page of the application.
     *
     * The package does not render those pages, so the application declares
     * them and names them in the configuration of the package. A missing
     * route is a mistake of the application, and it is said out loud instead
     * of failing with a message about routes that says nothing.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function applicationUrl(string $routeName, array $parameters = []): string
    {
        $this->assertRouteExists($routeName);

        return route($routeName, $parameters);
    }

    /**
     * The signed link that points at a page of the application.
     *
     * The signature says the link was written by the package and that it
     * expires, so the page of the application can trust it.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function signedApplicationUrl(string $routeName, array $parameters, int $lifetimeMinutes): string
    {
        $this->assertRouteExists($routeName);

        return URL::temporarySignedRoute(
            $routeName,
            now()->addMinutes($lifetimeMinutes),
            $parameters,
        );
    }

    /**
     * Make sure the application declares the route the mail points at.
     */
    private function assertRouteExists(string $routeName): void
    {
        if (Route::has($routeName)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'SivarCart necesita la ruta "%s" para escribirle al comprador. Declárala en tu aplicación o cambia el nombre de la ruta en la sección "buyers" de la configuración del paquete.',
            $routeName,
        ));
    }

    /**
     * Build the envelope of the email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->sender(),
            to: [$this->recipient()],
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
                'customer' => $this->customer,
            ], $this->templateData()),
        );
    }
}
