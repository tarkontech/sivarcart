<?php

declare(strict_types=1);

return [
    'order_notifications' => [

        'table' => [
            'product' => 'Producto',
            'quantity' => 'Cantidad',
            'price' => 'Precio',
        ],

        'totals' => [
            'subtotal' => 'Subtotal',
            'discount' => 'Descuento',
            'shipping' => 'Envío',
            'tax' => 'IVA',
            'total' => 'Total',
        ],

        'order_placed' => [
            'label' => 'Pedido recibido',
            'subject' => 'Recibimos tu pedido :number',
            'greeting' => 'Hola :name,',
            'body' => 'Gracias por tu compra. Este es el resumen del pedido :number.',
            'cta' => 'Ir a la tienda',
            'footer' => 'Te escribimos en cuanto el pedido avance.',
        ],

        'order_paid' => [
            'label' => 'Pago recibido',
            'subject' => 'El pedido :number está pagado',
            'greeting' => 'Hola :name,',
            'body' => 'Recibimos tu pago de :amount. Gracias.',
            'cta' => 'Ir a la tienda',
            'footer' => 'Te escribimos en cuanto el pedido salga.',
        ],

        'order_shipped' => [
            'label' => 'Pedido enviado',
            'subject' => 'Tu pedido :number va en camino',
            'greeting' => 'Hola :name,',
            'body' => 'Tu pedido :number salió de la tienda y ya va en camino.',
            'cta' => 'Ir a la tienda',
            'footer' => 'Gracias por comprar con nosotros.',
        ],

        'order_delivered' => [
            'label' => 'Pedido entregado',
            'subject' => 'El pedido :number fue entregado',
            'greeting' => 'Hola :name,',
            'body' => 'El pedido :number fue entregado. Esperamos que lo disfrutes.',
            'cta' => 'Ir a la tienda',
            'footer' => 'Gracias por comprar con nosotros.',
        ],

        'order_cancelled' => [
            'label' => 'Pedido cancelado',
            'subject' => 'El pedido :number fue cancelado',
            'greeting' => 'Hola :name,',
            'body' => 'El pedido :number fue cancelado. Si no fuiste tú, responde a este correo y lo revisamos.',
            'cta' => 'Ir a la tienda',
            'footer' => 'Gracias por comprar con nosotros.',
        ],

        'order_refunded' => [
            'label' => 'Pedido reembolsado',
            'subject' => 'El pedido :number fue reembolsado',
            'greeting' => 'Hola :name,',
            'body' => 'Devolvimos :amount del pedido :number. Según tu banco, el dinero tarda unos días en volver.',
            'cta' => 'Ir a la tienda',
            'footer' => 'Gracias por comprar con nosotros.',
        ],

        'payment_failed' => [
            'label' => 'Pago fallido',
            'subject' => 'No pudimos procesar tu pago',
            'greeting' => 'Hola :name,',
            'body' => 'El pago del pedido :number no pasó: :reason. Puedes intentarlo de nuevo desde la tienda.',
            'cta' => 'Ir a la tienda',
            'footer' => 'Gracias por comprar con nosotros.',
        ],

    ],
];
