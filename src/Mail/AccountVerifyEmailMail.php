<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Mail;

/**
 * The email the buyer receives to verify the email address of the account.
 */
final class AccountVerifyEmailMail extends BuyerAccountMail
{
    /**
     * How long the link of the verification holds.
     */
    private const int LINK_LIFETIME_MINUTES = 60;

    /**
     * The markdown template this mail renders.
     */
    protected function template(): string
    {
        return 'sivarcart::mail.account-verify-email';
    }

    /**
     * The key of the texts of this mail.
     */
    protected function subjectKey(): string
    {
        return 'verify_email';
    }

    /**
     * The data the template needs besides the customer.
     *
     * @return array<string, mixed>
     */
    protected function templateData(): array
    {
        return array_merge(parent::templateData(), [
            'url' => $this->verificationUrl(),
        ]);
    }

    /**
     * The signed link that verifies the email address.
     */
    private function verificationUrl(): string
    {
        $route = (string) config('sivarcart.buyers.email_verification_route', 'verification.verify');

        return $this->signedApplicationUrl($route, [
            'id' => $this->customer->getKey(),
            'hash' => $this->customer->emailVerificationHash(),
        ], self::LINK_LIFETIME_MINUTES);
    }
}
