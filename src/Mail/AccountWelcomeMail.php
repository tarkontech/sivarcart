<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Mail;

use SivarcartCore\SivarcartCore\Models\Customer;

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
        return 'sivarcart-core::mail.account-welcome';
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
