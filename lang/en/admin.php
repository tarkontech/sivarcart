<?php

declare(strict_types=1);

return [

    'title' => 'Administration',

    'dashboard' => [
        'title' => 'Dashboard',
        'description' => 'What is happening in the store right now.',
        'orders_today' => 'Orders today',
        'orders_awaiting' => 'Orders to prepare',
        'orders_unpaid' => 'Orders not paid',
        'products' => 'Products',
        'low_stock' => 'Low on stock',
    ],

    'orders' => [
        'title' => 'Orders',
        'search' => 'Search by number, name or email',
        'all_statuses' => 'Every status',
        'empty' => 'There are no orders that answer the search.',
        'not_found' => 'The order does not exist.',
        'back' => 'Back to the orders',
        'number' => 'Number',
        'customer' => 'Customer',
        'date' => 'Date',
        'total' => 'Total',
        'status' => 'Status',
        'payment' => 'Payment',
        'items' => 'Items',
        'item' => 'Item',
        'quantity' => 'Quantity',
        'price' => 'Price',
        'line_total' => 'Subtotal',
        'subtotal' => 'Subtotal',
        'discount' => 'Discount',
        'tax' => 'Tax',
        'shipping' => 'Shipping',
        'buyer' => 'Buyer',
        'email' => 'Email',
        'phone' => 'Phone',
        'actions' => 'Actions',
        'timeline' => 'History',
        'moved' => 'The order is now :status.',
    ],

    'products' => [
        'title' => 'Products',
        'search' => 'Search by name',
        'empty' => 'There are no products that answer the search.',
        'name' => 'Name',
        'sku' => 'SKU',
        'price' => 'Price',
        'stock' => 'Stock',
        'status' => 'Status',
    ],

    'customers' => [
        'title' => 'Customers',
        'search' => 'Search by name, email or phone',
        'empty' => 'There are no customers that answer the search.',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'orders' => 'Orders',
    ],

    'settings' => [
        'title' => 'Store settings',
        'description' => 'The data the store shows to its buyers.',
        'name' => 'Name',
        'legal_name' => 'Legal name',
        'tax_id' => 'Tax id',
        'email' => 'Email',
        'phone' => 'Phone',
        'save' => 'Save the settings',
        'saved' => 'The settings of the store were saved.',
    ],

    'roles' => [
        'title' => 'Roles',
        'description' => 'The roles of the staff of the store and the permissions they grant.',
        'name' => 'Name',
        'slug' => 'Slug',
        'permissions' => 'Permissions',
        'staff' => 'Staff',
    ],

    'login' => [
        'title' => 'Sign in',
        'email' => 'Email',
        'password' => 'Password',
        'remember' => 'Remember me',
        'submit' => 'Sign in',
        'failed' => 'Those credentials do not match our records.',
    ],

    'setup' => [
        'title' => 'One more step',
        'body' => 'The roles of the staff of the store live in the users of the application, so the model of the user has to carry the trait of the roles.',
        'trait' => 'Add the trait to the model of the user of the application:',
        'roles' => 'Then seed the roles of the store:',
    ],

    'pagination' => [
        'label' => 'Pagination',
        'previous' => 'Previous',
        'next' => 'Next',
    ],

];
