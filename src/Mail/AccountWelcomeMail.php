<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Mail;

use Tarkontech\SivarCart\Models\Customer;

/**
 * The email the buyer receives when the account is created.
 */
final class AccountWelcomeMail extends BuyerAccountMail
{
    /**
     * The markdown template this mail renders.
     */
    protected function template(): string
    {
        return 'sivarcart::mail.account-welcome';
    }

    /**
     * The key of the texts of this mail.
     */
    protected function subjectKey(): string
    {
        return 'welcome';
    }

    /**
     * The data the template needs besides the customer.
     *
     * @return array<string, mixed>
     */
    protected function templateData(): array
    {
        return array_merge(parent::templateData(), [
            'url' => url('/'),
        ]);
    }
}
