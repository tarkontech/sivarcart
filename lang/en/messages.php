<?php

declare(strict_types=1);

return [
    'order_notifications' => [

        'table' => [
            'product' => 'Product',
            'quantity' => 'Quantity',
            'price' => 'Price',
        ],

        'totals' => [
            'subtotal' => 'Subtotal',
            'discount' => 'Discount',
            'shipping' => 'Shipping',
            'tax' => 'Tax',
            'total' => 'Total',
        ],

        'order_placed' => [
            'label' => 'Order received',
            'subject' => 'We received your order :number',
            'greeting' => 'Hello :name,',
            'body' => 'Thank you for your purchase. This is the summary of the order :number.',
            'cta' => 'Go to the store',
            'footer' => 'We will write to you as soon as the order moves.',
        ],

        'order_paid' => [
            'label' => 'Payment received',
            'subject' => 'The order :number is paid',
            'greeting' => 'Hello :name,',
            'body' => 'We received your payment of :amount. Thank you.',
            'cta' => 'Go to the store',
            'footer' => 'We will write to you as soon as the order ships.',
        ],

        'order_shipped' => [
            'label' => 'Order shipped',
            'subject' => 'The order :number is on its way',
            'greeting' => 'Hello :name,',
            'body' => 'Your order :number left the store and is on its way.',
            'cta' => 'Go to the store',
            'footer' => 'Thanks for buying with us.',
        ],

        'order_delivered' => [
            'label' => 'Order delivered',
            'subject' => 'The order :number was delivered',
            'greeting' => 'Hello :name,',
            'body' => 'The order :number was delivered. We hope you enjoy it.',
            'cta' => 'Go to the store',
            'footer' => 'Thanks for buying with us.',
        ],

        'order_cancelled' => [
            'label' => 'Order cancelled',
            'subject' => 'The order :number was cancelled',
            'greeting' => 'Hello :name,',
            'body' => 'The order :number was cancelled. If you did not ask for it, reply to this email and we will look into it.',
            'cta' => 'Go to the store',
            'footer' => 'Thanks for buying with us.',
        ],

        'order_refunded' => [
            'label' => 'Order refunded',
            'subject' => 'The order :number was refunded',
            'greeting' => 'Hello :name,',
            'body' => 'We refunded :amount of the order :number. Depending on your bank, the money takes a few days to come back.',
            'cta' => 'Go to the store',
            'footer' => 'Thanks for buying with us.',
        ],

        'payment_failed' => [
            'label' => 'Payment failed',
            'subject' => 'We could not process your payment',
            'greeting' => 'Hello :name,',
            'body' => 'The payment of the order :number did not go through: :reason. You can try again from the store.',
            'cta' => 'Go to the store',
            'footer' => 'Thanks for buying with us.',
        ],

    ],
];
