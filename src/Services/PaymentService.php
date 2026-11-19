<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use Rmirandasv\Wompi\Webhooks\WompiWebhook;
use SivarcartCore\SivarcartCore\Enums\DocumentSequenceType;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentTransactionStatus;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\Payment;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Models\PaymentTransaction;

/**
 * Keeps the payments of the orders.
 */
final class PaymentService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly StoreService $stores,
    ) {
        //
    }

    /**
     * Create the payment an order is waiting for.
     */
    public function createPending(Order $order, PaymentMethod $method): Payment
    {
        return Payment::query()->create([
            'order_id' => $order->getKey(),
            'payment_method_id' => $method->getKey(),
            'method_code' => $method->code,
            'gateway' => $method->driver,
            'reference' => $this->sequences->next(
                DocumentSequenceType::PAYMENT,
                $this->stores->paymentNumberPrefix(),
            ),
            'status' => PaymentRecordStatus::PENDING,
            'amount' => (float) $order->total,
            'currency' => $order->currency,
        ]);
    }

    /**
     * Register the transaction a gateway reported.
     */
    public function recordTransaction(Payment $payment, WompiWebhook $webhook): PaymentTransaction
    {
        $amount = $webhook->amount();

        return PaymentTransaction::query()->create([
            'payment_id' => $payment->getKey(),
            'gateway' => 'wompi',
            'gateway_transaction_id' => $webhook->idTransaccion(),
            'gateway_payment_attempt_id' => $webhook->paymentAttemptId(),
            'idempotency_key' => $webhook->idempotencyKey(),
            'amount' => is_numeric($amount) ? (float) $amount : (float) $payment->amount,
            'currency' => $payment->currency,
            'status' => $webhook->isSuccessful()
                ? PaymentTransactionStatus::APPROVED
                : PaymentTransactionStatus::REJECTED,
            'is_approved' => $webhook->isApproved(),
            'is_production' => $webhook->isProduction(),
            'result_code' => $webhook->resultEnum(),
            'payment_method' => $webhook->paymentMethodEnum(),
            'authorization_code' => $webhook->authorizationCode(),
            'merchant_reference' => $webhook->reference(),
            'occurred_at' => $webhook->occurredAt(),
            'raw_payload' => $webhook->raw(),
        ]);
    }

    /**
     * Mark a payment as settled.
     */
    public function markPaid(Payment $payment, ?string $gatewayReference = null): Payment
    {
        $payment->status = PaymentRecordStatus::PAID;
        $payment->paid_at = now();

        if ($gatewayReference !== null && $gatewayReference !== '') {
            $payment->gateway_reference = $gatewayReference;
        }

        $payment->save();

        return $payment;
    }

    /**
     * Mark a payment as failed.
     */
    public function markFailed(Payment $payment, string $reason): Payment
    {
        $payment->status = PaymentRecordStatus::FAILED;
        $payment->failed_at = now();
        $payment->failure_reason = $reason;
        $payment->save();

        return $payment;
    }

    /**
     * Cancel a payment that was never settled.
     */
    public function cancel(Payment $payment): Payment
    {
        $payment->status = PaymentRecordStatus::CANCELLED;
        $payment->save();

        return $payment;
    }

    /**
     * Register a refund over a payment.
     */
    public function refund(Payment $payment, float $amount): Payment
    {
        $refunded = round((float) $payment->refunded_amount + $amount, 2);

        $payment->refunded_amount = $refunded;
        $payment->status = $refunded >= (float) $payment->amount
            ? PaymentRecordStatus::REFUNDED
            : PaymentRecordStatus::PARTIALLY_REFUNDED;
        $payment->save();

        return $payment;
    }
}
