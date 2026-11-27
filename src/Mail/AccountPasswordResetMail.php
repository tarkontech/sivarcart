<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Mail;

use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Support\BuyerRoutes;

/**
 * The email the buyer receives to reset the password of the account.
 */
final class AccountPasswordResetMail extends BuyerAccountMail
{
    /**
     * The token that proves the request comes from the buyer.
     */
    protected readonly string $token;

    /**
     * The customer this mail writes about and the token of the reset.
     */
    public function __construct(Customer $customer, string $token)
    {
        parent::__construct($customer);

        $this->token = $token;
    }

    /**
     * The markdown template this mail renders.
     */
    protected function template(): string
    {
        return 'sivarcart-core::mail.account-password-reset';
    }

    /**
     * The key of the texts of this mail.
     */
    protected function subjectKey(): string
    {
        return 'password_reset';
    }

    /**
     * The data the template needs besides the customer.
     *
     * @return array<string, mixed>
     */
    protected function templateData(): array
    {
        return array_merge(parent::templateData(), [
            'url' => $this->resetUrl(),
        ]);
    }

    /**
     * The link that opens the page where the password is changed.
     *
     * The page is la de la tienda when the store is on, and the one of the
     * application when the application names it in the configuration of the
     * package.
     */
    private function resetUrl(): string
    {
        return $this->applicationUrl(BuyerRoutes::passwordReset(), [
            'token' => $this->token,
            'email' => (string) $this->customer->getEmailForPasswordReset(),
        ]);
    }
}
