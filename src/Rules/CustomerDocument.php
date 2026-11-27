<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Tarkontech\SivarCart\Enums\CustomerDocumentType;

/**
 * Checks that the identity document of a buyer holds the format of its type.
 *
 * The rule knows the type of the document it checks, so the same rule serves
 * every type of the enum:
 *
 * ```php
 * 'document_number' => [new CustomerDocument(CustomerDocumentType::DUI)],
 * ```
 *
 * The document is checked as text and never as a number: the DUI of El
 * Salvador starts with the municipality code, and the leading zero is part of
 * the document. A form that hands the document over as a number loses it.
 *
 * The check digit of the DUI and the NIT comes from the algorithm the trade
 * uses; the tax administration does not publish it, so it is documented as a
 * community algorithm.
 */
final class CustomerDocument implements ValidationRule
{
    /**
     * The weights of the eight digits of the DUI.
     *
     * @var list<int>
     */
    private const array DUI_WEIGHTS = [9, 8, 7, 6, 5, 4, 3, 2];

    /**
     * The weights of the thirteen digits of the NIT of the old series.
     *
     * @var list<int>
     */
    private const array NIT_LEGACY_WEIGHTS = [14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2];

    /**
     * The weights of the thirteen digits of the NIT of the current series.
     *
     * @var list<int>
     */
    private const array NIT_CURRENT_WEIGHTS = [2, 7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    /**
     * The correlative from which the current series of the NIT begins.
     */
    private const int NIT_CURRENT_SERIES = 100;

    /**
     * The type of the document this rule checks.
     */
    public function __construct(private readonly ?CustomerDocumentType $type = null)
    {
        //
    }

    /**
     * Check the document of the buyer.
     *
     * The rule does nothing when the type of the document is not known: the
     * rule of the type itself is the one that asks for it.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->type === null) {
            return;
        }

        $reason = self::check($this->type, is_string($value) ? $value : '');

        if ($reason === null) {
            return;
        }

        $fail(self::message($this->type, $reason));
    }

    /**
     * The reason why the document does not hold the format of its type.
     *
     * The method returns null when the document is correct, so the domain can
     * use it without going through the validator.
     *
     * @return 'invalid_format'|'invalid_check_digit'|'invalid_length'|null
     */
    public static function check(CustomerDocumentType $type, string $document): ?string
    {
        return match ($type) {
            CustomerDocumentType::DUI => self::checkDui($document),
            CustomerDocumentType::NIT, CustomerDocumentType::RUC => self::checkNit($document),
            CustomerDocumentType::PASAPORTE => self::checkPassport($document),
            CustomerDocumentType::OTRO => self::checkOther($document),
        };
    }

    /**
     * The message that tells the buyer why the document was refused.
     */
    public static function message(CustomerDocumentType $type, string $reason): string
    {
        return (string) __('sivarcart::messages.customer_document.'.$reason, [
            'type' => $type->label(),
        ]);
    }

    /**
     * The DUI holds eight digits and a check digit.
     *
     * @return 'invalid_format'|'invalid_check_digit'|'invalid_length'|null
     */
    private static function checkDui(string $document): ?string
    {
        $digits = self::digits($document);

        if (strlen($digits) !== 9) {
            return 'invalid_length';
        }

        if ($digits === '000000000') {
            return 'invalid_format';
        }

        $sum = self::weightedSum($digits, self::DUI_WEIGHTS);
        $expected = (10 - ($sum % 10)) % 10;

        return ((int) $digits[8]) === $expected ? null : 'invalid_check_digit';
    }

    /**
     * The NIT holds fourteen digits and a check digit.
     *
     * The DUI is the tax id of a natural person since the unification of the
     * two documents, so a nine digit document is a valid NIT.
     *
     * @return 'invalid_format'|'invalid_check_digit'|'invalid_length'|null
     */
    private static function checkNit(string $document): ?string
    {
        $digits = self::digits($document);

        if (strlen($digits) === 9) {
            return self::checkDui($digits);
        }

        if (strlen($digits) !== 14) {
            return 'invalid_length';
        }

        $series = (int) substr($digits, 10, 3);

        if ($series > self::NIT_CURRENT_SERIES) {
            $sum = self::weightedSum($digits, self::NIT_CURRENT_WEIGHTS);
            $expected = ((11 - ($sum % 11)) % 11) % 10;

            return ((int) $digits[13]) === $expected ? null : 'invalid_check_digit';
        }

        $legacy = self::weightedSum($digits, self::NIT_LEGACY_WEIGHTS);

        if (((int) $digits[13]) === (($legacy % 11) % 10)) {
            return null;
        }

        // The band between the two series was never observed in a public
        // sample, so a document of that band is accepted with either table:
        // refusing a number that was really issued costs more than the odds
        // of letting a mistyped digit through.
        $current = self::weightedSum($digits, self::NIT_CURRENT_WEIGHTS);

        if (((int) $digits[13]) === (((11 - ($current % 11)) % 11) % 10)) {
            return null;
        }

        return 'invalid_check_digit';
    }

    /**
     * The passport holds six to twenty letters or digits.
     *
     * @return 'invalid_format'|'invalid_check_digit'|'invalid_length'|null
     */
    private static function checkPassport(string $document): ?string
    {
        $length = strlen($document);

        if ($length < 6 || $length > 20) {
            return 'invalid_length';
        }

        return preg_match('/^[A-Za-z0-9]+$/', $document) === 1 ? null : 'invalid_format';
    }

    /**
     * The other documents are free text, up to the length of the column.
     *
     * @return 'invalid_format'|'invalid_check_digit'|'invalid_length'|null
     */
    private static function checkOther(string $document): ?string
    {
        return strlen($document) <= 32 ? null : 'invalid_length';
    }

    /**
     * The digits of the document, without the separators.
     */
    private static function digits(string $document): string
    {
        return (string) preg_replace('/\D+/', '', $document);
    }

    /**
     * The sum of the digits of the document with the given weights.
     *
     * @param  list<int>  $weights
     */
    private static function weightedSum(string $digits, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += ((int) $digits[$index]) * $weight;
        }

        return $sum;
    }
}
