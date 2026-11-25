<?php

declare(strict_types=1);

return [

    'account_notifications' => [

        'welcome' => [
            'subject' => 'Your :store account is ready',
            'greeting' => 'Hello :name,',
            'body' => 'Your :store account is ready. From now on you sign in with your email and your password, and your purchases are kept in your account.',
            'cta' => 'Sign in to my account',
            'footer' => 'If you did not create the account, reply to this email and we will take a look at it.',
        ],

        'verify_email' => [
            'subject' => 'Verify your :store email',
            'greeting' => 'Hello :name,',
            'body' => 'Confirm this email and your :store account gets verified.',
            'cta' => 'Verify my email',
            'footer' => 'If you did not create the account, ignore this email.',
        ],

        'password_reset' => [
            'subject' => 'Reset your :store password',
            'greeting' => 'Hello :name,',
            'body' => 'Somebody asked to reset the password of your :store account. Use the link below to choose a new one.',
            'cta' => 'Reset my password',
            'footer' => 'If it was not you, ignore this email: your password stays the same.',
        ],

    ],

    'api' => [
        'invalid_credentials' => 'The credentials do not match.',
        'password_link_sent' => 'If the account exists, the password reset link is on its way.',
        'password_changed' => 'The password was changed.',
        'wrong_password' => 'The current password does not match.',
        'logged_out' => 'The session was closed.',
    ],

    'catalog_sort' => [
        'recent' => 'Most recent',
        'price_asc' => 'Price from low to high',
        'price_desc' => 'Price from high to low',
        'name' => 'Name',
    ],

    'coupon_type' => [
        'fixed' => 'Fixed amount',
        'percentage' => 'Percentage',
        'free_shipping' => 'Free shipping',
    ],

    'customer_document' => [
        'invalid_format' => 'The ":type" document does not have the right format.',
        'invalid_check_digit' => 'The check digit of the ":type" document does not match.',
        'invalid_length' => 'The ":type" document does not have the right length.',
    ],

    'customer_document_type' => [
        'nit' => 'NIT',
        'ruc' => 'RUC',
        'dui' => 'DUI',
        'pasaporte' => 'Passport',
        'otro' => 'Other',
    ],

    'document_sequence_type' => [
        'order' => 'Order',
        'payment' => 'Payment',
    ],

    'inventory_movement_type' => [
        'sale' => 'Sale',
        'restock' => 'Restock',
        'adjustment' => 'Adjustment',
        'return' => 'Return',
        'reservation' => 'Reservation',
        'release' => 'Release',
    ],

    'media' => [
        'invalid_type' => 'The file is not an image of a kind the store accepts.',
        'too_big' => 'The file is bigger than the :size KB the store accepts.',
    ],

    'order_history_axis' => [
        'status' => 'Order status',
        'payment' => 'Payment status',
    ],

    'order_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'returned' => 'Returned',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],

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
            'subject' => 'Your order :number is on its way',
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

    'payment_method_type' => [
        'online' => 'Online payment',
        'offline' => 'Offline payment',
    ],

    'payment_record_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
        'partially_refunded' => 'Partially refunded',
    ],

    'payment_status' => [
        'unpaid' => 'Unpaid',
        'pending' => 'Payment pending',
        'paid' => 'Paid',
        'failed' => 'Payment failed',
        'cancelled' => 'Payment cancelled',
        'refunded' => 'Refunded',
        'partially_refunded' => 'Partially refunded',
    ],

    'payment_transaction_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'failed' => 'Failed',
    ],

    'product_option_type' => [
        'text' => 'Text',
        'select' => 'Select',
        'color' => 'Color',
    ],

    'product_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'product_type' => [
        'simple' => 'Simple product',
        'variable' => 'Product with variants',
    ],

    'shipping_method_type' => [
        'flat_rate' => 'Flat rate',
        'free' => 'Free',
        'pickup' => 'Store pickup',
    ],

];
