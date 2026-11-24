<?php

declare(strict_types=1);

return [

    'account_notifications' => [

        'welcome' => [
            'subject' => 'Tu cuenta de :store está lista',
            'greeting' => 'Hola :name,',
            'body' => 'Tu cuenta de :store ya está lista. Desde ahora entras con tu correo y tu contraseña, y tus compras quedan guardadas en tu cuenta.',
            'cta' => 'Entrar a mi cuenta',
            'footer' => 'Si no fuiste tú quien creó la cuenta, responde a este correo y la revisamos.',
        ],

        'verify_email' => [
            'subject' => 'Verifica tu correo de :store',
            'greeting' => 'Hola :name,',
            'body' => 'Confirma este correo y tu cuenta de :store queda verificada.',
            'cta' => 'Verificar mi correo',
            'footer' => 'Si no fuiste tú quien creó la cuenta, ignora este correo.',
        ],

        'password_reset' => [
            'subject' => 'Restablece tu contraseña de :store',
            'greeting' => 'Hola :name,',
            'body' => 'Alguien pidió restablecer la contraseña de tu cuenta de :store. Usa el enlace de abajo para elegir una nueva.',
            'cta' => 'Restablecer mi contraseña',
            'footer' => 'Si no fuiste tú, ignora este correo: tu contraseña sigue siendo la misma.',
        ],

    ],

    'catalog_sort' => [
        'recent' => 'Más recientes',
        'price_asc' => 'Precio de menor a mayor',
        'price_desc' => 'Precio de mayor a menor',
        'name' => 'Nombre',
    ],

    'coupon_type' => [
        'fixed' => 'Monto fijo',
        'percentage' => 'Porcentaje',
        'free_shipping' => 'Envío gratis',
    ],

    'customer_document' => [
        'invalid_format' => 'El documento de tipo ":type" no tiene el formato correcto.',
        'invalid_check_digit' => 'El dígito verificador del documento de tipo ":type" no cuadra.',
        'invalid_length' => 'El documento de tipo ":type" no tiene la longitud correcta.',
    ],

    'customer_document_type' => [
        'nit' => 'NIT',
        'ruc' => 'RUC',
        'dui' => 'DUI',
        'pasaporte' => 'Pasaporte',
        'otro' => 'Otro',
    ],

    'document_sequence_type' => [
        'order' => 'Orden',
        'payment' => 'Pago',
    ],

    'inventory_movement_type' => [
        'sale' => 'Venta',
        'restock' => 'Reposición',
        'adjustment' => 'Ajuste',
        'return' => 'Devolución',
        'reservation' => 'Reserva',
        'release' => 'Liberación',
    ],

    'order_history_axis' => [
        'status' => 'Estado de la orden',
        'payment' => 'Estado del pago',
    ],

    'order_status' => [
        'pending' => 'Pendiente',
        'processing' => 'En preparación',
        'shipped' => 'Enviada',
        'delivered' => 'Entregada',
        'returned' => 'Devuelta',
        'cancelled' => 'Cancelada',
        'refunded' => 'Reembolsada',
    ],

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

    'payment_method_type' => [
        'online' => 'Pago en línea',
        'offline' => 'Pago fuera de línea',
    ],

    'payment_record_status' => [
        'pending' => 'Pendiente',
        'processing' => 'Procesando',
        'paid' => 'Pagado',
        'failed' => 'Fallido',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado',
        'partially_refunded' => 'Reembolso parcial',
    ],

    'payment_status' => [
        'unpaid' => 'Sin pagar',
        'pending' => 'Pago pendiente',
        'paid' => 'Pagada',
        'failed' => 'Pago fallido',
        'cancelled' => 'Pago cancelado',
        'refunded' => 'Reembolsada',
        'partially_refunded' => 'Reembolso parcial',
    ],

    'payment_transaction_status' => [
        'pending' => 'Pendiente',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
        'failed' => 'Fallida',
    ],

    'product_option_type' => [
        'text' => 'Texto',
        'select' => 'Selección',
        'color' => 'Color',
    ],

    'product_status' => [
        'draft' => 'Borrador',
        'published' => 'Publicado',
        'archived' => 'Archivado',
    ],

    'product_type' => [
        'simple' => 'Producto simple',
        'variable' => 'Producto con variantes',
    ],

    'shipping_method_type' => [
        'flat_rate' => 'Tarifa fija',
        'free' => 'Gratis',
        'pickup' => 'Recoger en tienda',
    ],

];
